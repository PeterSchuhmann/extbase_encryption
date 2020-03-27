<?php

namespace PS\ExtbaseEncryption\Hooks;


use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * class to extend backend search to look for encrypted values in database
 *
 * Class BackendSearchHook
 * @package PS\ExtbaseEncryption\Hooks
 */
class BackendSearchHook
{
    public function makeSearchStringConstraints($queryBuilder, $constraints, $searchString, $table, $currentPid)
    {

        $searchableFields = $this->getSearchFields($table);
        $expressionBuilder = $queryBuilder->expr();
        $tablePidField = $table === 'pages' ? 'uid' : 'pid';

        $encryptor = Encryptor::init();

        if (MathUtility::canBeInterpretedAsInteger($searchString)) {

        } elseif (!empty($searchableFields)) {
            $constraints = [];
            $like = $queryBuilder->quote('%' . $queryBuilder->escapeLikeWildcards($searchString) . '%');
            foreach ($searchableFields as $fieldName) {
                if (!isset($GLOBALS['TCA'][$table]['columns'][$fieldName])) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];
                $fieldType = $fieldConfig['type'];
                $evalRules = $fieldConfig['eval'] ?: '';
                $searchConstraint = $expressionBuilder->andX(
                    $searchConstraint = $expressionBuilder->orX(
                        $expressionBuilder->comparison(
                            'LOWER(' . $queryBuilder->quoteIdentifier($fieldName) . ')',
                            'LIKE',
                            'LOWER(' . $like . ')'
                        ),
                        $expressionBuilder->comparison(
                            'LOWER(' . $queryBuilder->quoteIdentifier($fieldName) . ')',
                            'LIKE',
                            $queryBuilder->quote('%' . $queryBuilder->escapeLikeWildcards($encryptor->encrypt($searchString)) . '%')
                        )
                    )
                );
                if (is_array($fieldConfig['search'])) {
                    $searchConfig = $fieldConfig['search'];
                    if (in_array('case', $searchConfig)) {
                        // Replace case insensitive default constraint
                        $searchConstraint = $expressionBuilder->andX($expressionBuilder->like($fieldName, $like));
                    }
                    if (in_array('pidonly', $searchConfig) && $currentPid > 0) {
                        $searchConstraint->add($expressionBuilder->eq($tablePidField, (int)$currentPid));
                    }
                    if ($searchConfig['andWhere']) {
                        $searchConstraint->add(
                            QueryHelper::stripLogicalOperatorPrefix($fieldConfig['search']['andWhere'])
                        );
                    }
                }
                if ($fieldType === 'text'
                    || $fieldType === 'flex'
                    || $fieldType === 'input' && (!$evalRules || !preg_match('/date|time|int/', $evalRules))
                ) {
                    if ($searchConstraint->count() !== 0) {
                        $constraints[] = $searchConstraint;
                    }
                }
            }
        }

        return $constraints;
    }

    /**
     * Fetches a list of fields to use in the Backend search for the given table.
     *
     * @param string $tableName
     * @return string[]
     */
    protected function getSearchFields($tableName)
    {
        $fieldArray = [];
        $fieldListWasSet = false;
        // Get fields from ctrl section of TCA first
        if (isset($GLOBALS['TCA'][$tableName]['ctrl']['searchFields'])) {
            $fieldArray = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'], true);
            $fieldListWasSet = true;
        }
        // Call hook to add or change the list
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['mod_list']['getSearchFieldList'])) {
            $hookParameters = [
                'tableHasSearchConfiguration' => $fieldListWasSet,
                'tableName' => $tableName,
                'searchFields' => &$fieldArray,
                'searchString' => $this->searchString
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['mod_list']['getSearchFieldList'] as $hookFunction) {
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
            }
        }
        return $fieldArray;
    }

}
