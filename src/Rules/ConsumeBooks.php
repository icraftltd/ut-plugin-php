<?php

namespace Zw\Plugin\Ut\Rules;

use Zw\Plugin\Ut\Rule;
use Zw\Plugin\Ut\SqlBuilder;

/**
 * @rule consumeBooks: 订阅书籍: [1,2,3,4]
 */
class ConsumeBooks extends Rule
{
    /**
     * @var array|string[]
     */
    protected $validationRules = [
        'field' => 'require|eq:consumeBooks', 'calc' => 'require|in:in,notIn', 'value' => 'require|array|min:1'
    ];

    /**
     * @param array $meta
     * @param string $tablePrefix
     */
    public function __construct(array $meta, string $tablePrefix = '')
    {
        $this->meta = $meta;
        if ($tablePrefix) {
            $this->setTableAlias($tablePrefix);
        }
    }

    /**
     * @param int $dbmsType
     * @return string
     */
    public function getSqlSegment(int $dbmsType = SqlBuilder::MySQL): string
    {
        $field = $this->getField('subsribe_book');
        if (! count($this->getValues())) {
            return '/** consumeBooks with empty items */';
        }

        $segment = null;
        $eq = $this->getMeta('calc') == 'in' ? 1 : 0;
        switch ($dbmsType) {
        case SqlBuilder::MySQL:
            $values = implode('|', $this->getValues());
            $segment = "{$field} IS NOT NULL AND (REPLACE({$field}, ',', '|') REGEXP '{$values}') = {$eq}";

            break;
        case SqlBuilder::ClickHouse:
            $values = implode(',', static::wrapQuota($this->getValues()));
            $segment = "({$field} != [] AND hasAny({$field}, [{$values}]) = {$eq})";

            break;
        }

        return join(' ', array_filter([$segment]));
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return array_map(function ($id) { return "{$id}"; }, (array) $this->getMeta('value'));
    }
}