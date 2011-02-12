<?php
// External error handlers - not actually depreciated, but homeless until the new package family stuff is setup
class Twig_Error extends MortarError {}

// depreciated classes- there isn't any harm to them, as they're just extensions of the new ones, but I want to
// standardize on names.
class CoreError extends MortarError {}
class CoreWarning extends MortarWarning {}
class CoreNotice extends MortarNotice {}
class CoreInfo extends MortarException {}

class CoreSecurity extends MortarError {}
class CoreMaintenanceMode extends MortarException {}

class CoreUserError extends MortarUserError {}
class CoreDepreciated extends MortarDepreciated {}
class CoreDepreciatedError extends MortarError {}

class AuthenticationError extends MortarUserWarning {}
class ResourceNotFoundError extends MortarUserNotice {}
class ResourceMoved extends MortarUserNotice {}

class TypeMismatch extends MortarError
{
	/**
	 * Exception-specific constructor to allow additional information to be passed to the thrown exception.
	 *
	 * @param array $message [0] should be the expected type, [1] should be the received object, and [2] is an optional message
	 * @param int $code
	 */
	public function __construct($message = '', $code = 0)
	{
		if(is_array($message))
		{
			$expectedType = isset($message[0]) ? $message[0] : '';
			$customMessage = isset($message[2]) ? $message[2] : '';

			if(isset($message[1]))
			{
				$receivedObject = $message[1];
				$receivedType = is_object($receivedObject)
									? 'Class ' . get_class($receivedObject)
									: gettype($receivedObject);
			}else{
				$receivedType = 'Null or Unknown';

			}

			$message = 'Expected object of type: ' . $expectedType . ' but received ' . $receivedType . ' ';

			if(isset($customMessage))
				$message .= ' ' . $customMessage;
		}

		parent::__construct($message, $code);
	}
}
?>