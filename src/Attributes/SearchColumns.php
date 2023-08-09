<?php

namespace Abdo\Searchable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SearchColumns {

    public function __construct() {}
}