<?php

/**
 * This file is part of the package netresearch/rte-ckeditor-image.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    'RTE.default.proc.overruleMode := addToList(default)'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    'RTE.default.proc.overruleMode := addToList(rtehtmlarea_images_db)'
);

// Process the text inserted into TCA text field before the core processing tags place
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
    [\Netresearch\RteCKEditorImage\FormDataProvider\TransformRteDataProvider::class] = [
        'before' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class,
        ]
    ];

// Process the modified text from TCA text field before its stored in the database
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
    = \Netresearch\RteCKEditorImage\DataHandling\DataHandler::class;
