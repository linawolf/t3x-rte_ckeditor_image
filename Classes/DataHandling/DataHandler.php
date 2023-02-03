<?php

/**
 * This file is part of the package netresearch/rte-ckeditor-image.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\RteCKEditorImage\DataHandling;

use Netresearch\RteCKEditorImage\Database\RteImagesDbHook;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Hooks for data handler.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license https://www.gnu.org/licenses/agpl-3.0.de.html
 * @link    https://www.netresearch.de
 */
class DataHandler
{
    /**
     * Process the modified text from TCA text field before its stored in the database.
     *
     * @param string                                   $status
     * @param string                                   $table
     * @param string                                   $id
     * @param array                                    &$fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     *
     * @return void
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        string $id,
        array &$fieldArray,
        \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
    ): void {

//DebuggerUtility::var_dump(__METHOD__);
//DebuggerUtility::var_dump($status);
//DebuggerUtility::var_dump($table);
//DebuggerUtility::var_dump($id);
//DebuggerUtility::var_dump($fieldArray);

        foreach ($fieldArray as $field => $fieldValue) {
            // The field must be editable. Checking if a value for language can be changed.
            if (($GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? false)
                && ((string)$GLOBALS['TCA'][$table]['ctrl']['languageField']) === ((string)$field)
                && !$this->getBackendUser()->checkLanguageAccess($fieldValue)
            ) {
                continue;
            }

            // Ignore disabled fields
            if ($dataHandler->data_disableFields[$table][$id][$field] ?? false) {
                continue;
            }

            // Ignore not existing fields in TCA definition
            if (!isset($GLOBALS['TCA'][$table]['columns'][$field])) {
                continue;
            }

            // Getting config for the field
            $tcaFieldConf = $this->resolveFieldConfigurationAndRespectColumnsOverrides(
                $dataHandler,
                $table,
                $field
            );

            // Handle only fields of type "text"
            if (empty($tcaFieldConf['type'])
                || ($tcaFieldConf['type'] !== 'text')
            ) {
                continue;
            }

            // Ignore all none RTE text fields
            if (!isset($tcaFieldConf['enableRichtext'])
                || ($tcaFieldConf['enableRichtext'] === false)
            ) {
                continue;
            }

            $rteImageDbHook = GeneralUtility::makeInstance(RteImagesDbHook::class);
            $rteHtmlParser  = GeneralUtility::makeInstance(RteHtmlParser::class);

            $fieldArray[$field] = $rteImageDbHook->transform_db($fieldArray[$field], $rteHtmlParser);
        }
    }

    /**
     * Returns the current backend user authentication instance.
     *
     * @return BackendUserAuthentication
     */
    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Use columns overrides for evaluation.
     *
     * Fetch the TCA ["config"] part for a specific field, including the columnsOverrides value.
     * Used for checkValue purposes currently (as it takes the checkValue_currentRecord value).
     *
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @param string                                   $table
     * @param string                                   $field
     *
     * @return array
     *
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::resolveFieldConfigurationAndRespectColumnsOverrides
     */
    private function resolveFieldConfigurationAndRespectColumnsOverrides(
        \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler,
        string $table,
        string $field
    ): array {
        $tcaFieldConf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        $recordType   = BackendUtility::getTCAtypeValue($table, $dataHandler->checkValue_currentRecord);

        $columnsOverridesConfigOfField = $GLOBALS['TCA'][$table]['types'][$recordType]['columnsOverrides'][$field]['config'] ?? null;

        if ($columnsOverridesConfigOfField) {
            ArrayUtility::mergeRecursiveWithOverrule($tcaFieldConf, $columnsOverridesConfigOfField);
        }

        return $tcaFieldConf;
    }
}
