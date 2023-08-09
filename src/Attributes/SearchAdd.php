<?php

namespace Abdo\Searchable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class SearchAdd {

    public function __construct(public string $colName) {}

}