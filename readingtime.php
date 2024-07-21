<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class ReadingTimePlugin extends Plugin
{
    public static function getSubscribedEvents()
    {
        return [
            'onPageContentProcessed' => ['onPageContentProcessed', 0]
        ];
    }

    public function onPageContentProcessed(Event $event)
    {
        $page = $event['page'];
        $cacheKey = 'readingtime-' . $page->id();

        $readingTime = $this->grav['cache']->fetch($cacheKey);

        if ($readingTime === false) {
            $content = $page->content();
            $readingTime = $this->calculateReadingTime($content);
            $this->grav['cache']->save($cacheKey, $readingTime);
        } 

        $this->modifyHeader($page, $readingTime);
    }

    private function modifyHeader($page, $readingTime)
    {
        $header = $page->header();
        if (isset($header)) {
            // --- Translate Reading Time (without seconds) ---
            $language = $this->grav['language'];
            $options = array_merge($this->grav['config']->get('plugins.readingtime'), []); 
            $minutes_short_count = $readingTime;

            $minutes_text = ($minutes_short_count == 1) ? 
                $language->translate('PLUGIN_READINGTIME.MINUTE') : 
                $language->translate('PLUGIN_READINGTIME.MINUTES');

            $readingTimeString = sprintf(
                '%s: %s %s', 
                $language->translate('PLUGIN_READINGTIME.READING_LABEL'),
                $minutes_short_count,
                $minutes_text
            );

            $header->readingTime = $readingTimeString;
            $page->rawRoute($page->route(), $header); 
        } 
    }

    private function calculateReadingTime($text)
    {
        $wordCount = str_word_count(strip_tags($text));
        $wordsPerMinute = 200;
        $readingTime = ceil($wordCount / $wordsPerMinute);

        return $readingTime;
    }
}
