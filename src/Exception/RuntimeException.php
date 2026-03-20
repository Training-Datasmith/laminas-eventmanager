<?php

declare (strict_types=1);
namespace Laminas\Event_Manager\Exception;

use RuntimeException as SplRuntimeException;
/**
 * Runtime exception
 *
 * @final This class should not be extended
 */
class RuntimeException extends Spl_Runtime_Exception implements Exception_Interface
{
}