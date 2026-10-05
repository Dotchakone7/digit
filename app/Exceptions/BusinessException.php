<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Business-rule violation whose message is written for end users
 * (stock exhausted, invalid coupon, forbidden status change…).
 */
class BusinessException extends RuntimeException {}
