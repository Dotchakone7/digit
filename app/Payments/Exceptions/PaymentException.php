<?php

namespace App\Payments\Exceptions;

use RuntimeException;

/** Error whose message is safe to show to the customer. */
class PaymentException extends RuntimeException {}
