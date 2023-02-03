<?php

/**
 * This file is part of the package netresearch/rte-ckeditor-image.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\RteCKEditorImage\FormDataProvider;

use Netresearch\RteCKEditorImage\Database\RteImagesDbHook;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TODO
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license https://www.gnu.org/licenses/agpl-3.0.de.html
 * @link    https://www.netresearch.de
 */
class TransformRteDataProvider implements FormDataProviderInterface
{
    /**
     * Add transformed RTE text into $result data array.
     *
     * @param array $result Initialized result array
     *
     * @return array Result filled with more data
     */
    public function addData(array $result): array
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            // Handle only fields of type "text"
            if (empty($fieldConfig['config']['type'])
                || ($fieldConfig['config']['type'] !== 'text')
            ) {
                continue;
            }

            // Ignore all none RTE text fields
            if (!isset($fieldConfig['config']['enableRichtext'])
                || ($fieldConfig['config']['enableRichtext'] === false)
                || ($this->getBackendUser()->isRTE() === false)
            ) {
                continue;
            }

            // Ignore empty fields
            if ($result['databaseRow'][$fieldName] === null) {
                continue;
            }

            $rteImageDbHook = GeneralUtility::makeInstance(RteImagesDbHook::class);
            $rteHtmlParser  = GeneralUtility::makeInstance(RteHtmlParser::class);

            $result['databaseRow'][$fieldName] = $rteImageDbHook->transform_rte(
                $result['databaseRow'][$fieldName],
                $rteHtmlParser
            );
        }

        return $result;
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
}
