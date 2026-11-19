<?php

namespace App\Ai;

use RuntimeException;

/** Provider/configuration failure with a message that is safe to show in the admin (never contains the API key). */
class AiException extends RuntimeException {}
