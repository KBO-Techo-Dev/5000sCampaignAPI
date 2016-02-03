<?php
 class ReturnStatus {
 	const SUCCESS 					= 200;
 	const ENCRYPT_FAILED 			= 301;
 	const SESSION_NOT_FOUND 		= 302;
 	const INCORRECT_STRUCTURE 		= 304;
 	const API_WAS_NOT_FOUND			= 404;
 	const INTERNAL_SERVER_ERROR		= 500;
 	const SERVER_MAINTENANCE		= 501;
 	const USER_WAS_NOT_FOUND		= 502;
	const EMAIL_NOT_FOUND			= 503;
 	const DUPLICATED_USER			= 504;
	const DATA_NOT_FOUND			= 505;
 	const ACCOUNT_WAS_BANNED		= 601;
	const ALREADY_SIGN_TO_SUPPORT	= 701;
	const FAILED_SIGN_TO_SUPPORT	= 702;
 }