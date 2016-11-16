<?php
/*
 * This file is part of the OrderPdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\OrderPdf\Event;

use Eccube\Application;

/**
 * Class Common Event.
 */
class CommonEvent
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * AbstractEvent constructor.
     * @param \Silex\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Render position.
     *
     * @param string $html
     * @param string $part
     *
     * @return string
     */
    public function renderPosition($html, $part)
    {
        // For old and new ec-cube version
        // Search group
        // Group 1
        $search = '/(<li\s+id="dropmenu"[\s\S]*)'; // Points to start the search.
        // Group 2
        $search .= '(<ul\s+class="dropdown\-menu"[\s\S]*)'; // start drop down section.
        // Group 3
        $search .= '(<\/li>[\n\s]*<\/ul>)'; // The end of the dropdown section.
        // Group 4
        $search .= '([\s\S]*<form\s+id="dropdown\-form")/'; // Points to end the search.

        $arrMatch = array();
        preg_match($search, $html, $arrMatch, PREG_OFFSET_CAPTURE);

        if (!isset($arrMatch[4])) {
            return $html;
        }
        $oldHtml = $arrMatch[2][0];

        // first html
        $oldHtmlStartPos = $arrMatch[2][1];
        $firstHalfHtml = substr($html, 0, $oldHtmlStartPos);

        // end html
        $oldHtmlEndPos = $arrMatch[3][1];
        $endHalfHtml = substr($html, $oldHtmlEndPos);

        // new html
        $newHtml = str_replace('</ul>', $part.'</ul>', $oldHtml);

        $html = $firstHalfHtml.$newHtml.$endHalfHtml;

        return $html;
    }
}
