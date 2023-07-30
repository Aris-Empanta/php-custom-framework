<?php

namespace Libraries;

use DateTime;

class ErrorHandler
{
    
    public static function handleNonFatalErrors($errorNumber, $errorMessage, $errorFile, $errorLine)
    {
        // We clean the output buffer in order to render the error page below.
        ob_clean();
        
        /*
            We sanitize the error message to avoid potential Cross-Site Scripting attacks.
            ENT_QUOTES is used as the second parameter, which ensures that both single and double 
            quotes are converted to their respective HTML entities. The third parameter, 'UTF-8', 
            specifies the character set as UTF-8, which is commonly used for web applications.
        */
        $errorMessage = htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');

        $errorsLog = dirname(__DIR__). ERRORS_LOG;

        if (!is_dir(dirname($errorsLog))) {
            mkdir(dirname($errorsLog), 0755, true);
        }
            
        $formattedErrorMessage = ErrorHandler::formatErrorMessage($errorNumber, $errorMessage, $errorFile, $errorLine);     
        
        //In production the visitor should not see the error for security reasons
        if (DEBUG_MODE) {
            //show the error
            echo $formattedErrorMessage;
        }
        else {
            //show the error page
            $errorControllerNamespace = ERROR_CONTROLLER_NAMESPACE;
            $action = ERROR_CONTROLLER_ACTION;

            $errorControllerNamespace::$action();
        }
        

        $fileHandle = fopen($errorsLog, 'a+');
        if ($fileHandle) {
            if (flock($fileHandle, LOCK_EX)) {
                fwrite($fileHandle, $formattedErrorMessage);
                flock($fileHandle, LOCK_UN);
            }
            fclose($fileHandle);
        }

        exit();
    }

    public static function handleFatalErrors()
    {
        $error = error_get_last();
    
        if ($error !== null) {
            $errorNumber = $error['type'];
            $errorMessage = htmlspecialchars($error['message'], ENT_QUOTES, 'UTF-8');
            $errorFile = $error['file'];
            $errorLine = $error['line'];
    
            // We handle all the errors that a common error handler cannot handle, like fatal errors
            if (in_array($errorNumber, [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
                // We clean the output buffer in order to render the error page below.
                ob_clean();
                $errorsLog = dirname(__DIR__) . ERRORS_LOG;

                if (!is_dir(dirname($errorsLog))) {
                    mkdir(dirname($errorsLog), 0755, true);
                }

                $writeToFile = fopen($errorsLog, 'a+'); // Use 'a' mode for appending
    
                if ($writeToFile !== false) {
                    $errorMessage = ErrorHandler::formatErrorMessage($errorNumber, $errorMessage, $errorFile, $errorLine);
    
                    //In production the visitor should not see the error for security reasons
                    if (DEBUG_MODE) {
                        //show the error
                        echo $errorMessage;
                    }
                    else {
                        //show the error page
                        $errorControllerNamespace = ERROR_CONTROLLER_NAMESPACE;
                        $action = ERROR_CONTROLLER_ACTION;

                        $errorControllerNamespace::$action();
                    }
    
                    fwrite($writeToFile, $errorMessage);
    
                    fclose($writeToFile);
                }
            }
        }
    
        exit();
    }

    //The method to format the error message that will be save in the errors' log
    private static function formatErrorMessage($errorNumber, $errorMessage, $errorFile, $errorLine) {

        //We save the current date in a variable
        $date = new DateTime('now');

        //We access all the PHP defined constants
        $definedConstants = get_defined_constants(true)['Core'];

        //We save in an array all the constants that contain error codes
        $allErrorCodes = array_filter($definedConstants, function ($const) : bool {

            $firstTwoChars = substr($const, 0, 2);

            return $firstTwoChars === 'E_';
        }, ARRAY_FILTER_USE_KEY);

        //We save the error type key in a string variable, e.g. E_DEPRECATED
        $errorType = '';

        foreach($allErrorCodes as $key => $value) {
            if($value === $errorNumber)
                $errorType = $key;
        }

        return  '[' . $date->format('r') . '] [ Error Type: ' . $errorType . '] ' 
                . $errorMessage . ' - OCCURED IN ' . $errorFile . ' IN LINE '
                . $errorLine . PHP_EOL;
    }
}