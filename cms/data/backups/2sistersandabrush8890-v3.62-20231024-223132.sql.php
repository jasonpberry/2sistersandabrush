-- <?php die('This is not a program file.'); exit; ?>


--
-- Table structure for table `#TABLE_PREFIX#__accesslist`
--

DROP TABLE IF EXISTS `#TABLE_PREFIX#__accesslist`;

CREATE TABLE `#TABLE_PREFIX#__accesslist` (
  `num` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userNum` int(10) unsigned NOT NULL,
  `tableName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accessLevel` tinyint(3) unsigned NOT NULL,
  `maxRecords` int(10) unsigned DEFAULT NULL,
  `randomSaveId` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


--
-- Dumping data for table `#TABLE_PREFIX#__accesslist`
--

INSERT INTO `#TABLE_PREFIX#__accesslist` VALUES("1","1","all","9",NULL,"1234567890");
INSERT INTO `#TABLE_PREFIX#__accesslist` VALUES("19","2","all","1",NULL,"65387b31d54c59.85956103");
INSERT INTO `#TABLE_PREFIX#__accesslist` VALUES("20","2","accounts","9",NULL,"65387b31d54c59.85956103");
INSERT INTO `#TABLE_PREFIX#__accesslist` VALUES("21","2","weddings","9",NULL,"65387b31d54c59.85956103");
INSERT INTO `#TABLE_PREFIX#__accesslist` VALUES("22","2","_media","0",NULL,"65387b31d54c59.85956103");

--
-- Table structure for table `#TABLE_PREFIX#__email_templates`
--

DROP TABLE IF EXISTS `#TABLE_PREFIX#__email_templates`;

CREATE TABLE `#TABLE_PREFIX#__email_templates` (
  `num` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `createdDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `createdByUserNum` int(10) unsigned NOT NULL,
  `updatedDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatedByUserNum` int(10) unsigned NOT NULL,
  `template_id` mediumtext COLLATE utf8mb4_unicode_ci,
  `description` mediumtext COLLATE utf8mb4_unicode_ci,
  `from` mediumtext COLLATE utf8mb4_unicode_ci,
  `reply-to` mediumtext COLLATE utf8mb4_unicode_ci,
  `to` mediumtext COLLATE utf8mb4_unicode_ci,
  `cc` mediumtext COLLATE utf8mb4_unicode_ci,
  `bcc` mediumtext COLLATE utf8mb4_unicode_ci,
  `subject` mediumtext COLLATE utf8mb4_unicode_ci,
  `html` mediumtext COLLATE utf8mb4_unicode_ci,
  `placeholders` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


--
-- Dumping data for table `#TABLE_PREFIX#__email_templates`
--

INSERT INTO `#TABLE_PREFIX#__email_templates` VALUES("1","2023-10-24 20:53:32","0","2023-10-24 20:53:32","0","USER-PASSWORD-RESET","Website users get this email when they request a password reset","#settings.adminEmail#","#settings.adminEmail#","#user.email#","","","#server.http_host# Password Reset","<p>Hi #user.username#,</p>\r\n<p>You requested a password reset for #server.http_host#.</p>\r\n<p>To reset your password click this link:<br><a href=\"#resetUrl#\">#resetUrl#</a></p>\r\n<p>This request was made from IP address: #server.remote_addr#</p>","#user.username#\n#user.email#\n#loginUrl#\n#resetUrl#\n\n#server.http_host#\n#server.remote_addr#\n#settings.adminEmail#\n#settings.adminUrl#\n#settings.developerEmail#\n#settings.programName#");
INSERT INTO `#TABLE_PREFIX#__email_templates` VALUES("2","2023-10-24 20:53:32","0","2023-10-24 20:53:32","0","USER-SIGNUP","Website users receive this email when they sign up with their password.","#settings.adminEmail#","#settings.adminEmail#","#user.email#","","","#server.http_host# Account Details","<p>Hi #user.username#,</p>\r\n<p>Thanks for signing up to #server.http_host#.</p>\r\n<p>Your username is: #user.username#<br>Your password is: #user.password#</p>\r\n<p>Please click here to login:<br><a href=\"#loginUrl#\">#loginUrl#</a></p>\r\n<p>Thanks!</p>","#user.username#\n#user.email#\n#user.password#\n#loginUrl#\n#resetUrl#\n\n#server.http_host#\n#server.remote_addr#\n#settings.adminEmail#\n#settings.adminUrl#\n#settings.developerEmail#\n#settings.programName#");
INSERT INTO `#TABLE_PREFIX#__email_templates` VALUES("3","2023-10-24 20:53:32","0","2023-10-24 20:53:32","0","CMS-PASSWORD-RESET","Sent to users when they request to reset their password","#settings.adminEmail#","#settings.adminEmail#","#user.email#","","","#settings.programName# Password Reset","<p>Hi #user.email#,</p>\r\n<p>You requested a password reset for #settings.programName#.</p>\r\n<p>To reset your password click this link:<br><a href=\"#resetUrl#\">#resetUrl#</a></p>\r\n<p>This request was made from IP address: #server.remote_addr#</p>","#user.num#\n#user.email#\n#user.username#\n#user.fullname#\n#resetUrl#\n\n#server.http_host#\n#server.remote_addr#\n#settings.adminEmail#\n#settings.adminUrl#\n#settings.developerEmail#\n#settings.programName#");
INSERT INTO `#TABLE_PREFIX#__email_templates` VALUES("4","2023-10-24 20:53:32","0","2023-10-24 20:53:32","0","CMS-PASSWORD-RESET-FR","Sent to users when they request to reset their password (French)","#settings.adminEmail#","#settings.adminEmail#","#user.email#","","","#settings.programName# Réinitialisation de votre mot de passe","<p>Bonjour #user.email#,</p>\r\n<p>Vous avez demandé la réinitialisation de votre mot de passe.</p>\r\n<p>Pour réinitialiser votre mot de passe cliquez sur le lien ci-dessous:<br><a href=\"#resetUrl#\">#resetUrl#</a></p>\r\n<p></p>\r\n<p>Cette demande a été faite à partir de l\'adresse d\'IP : #server.remote_addr#</p>\r\n<p>Ne soyez pas inquiet si vous n\'êtes pas à l\'origine de cette demande, ces informations sont envoyées uniquement à votre adresse e-mail.</p>\r\n<p>L\'administrateur</p>\r\n<p>#settings.programName#</p>","#user.num#\n#user.email#\n#user.username#\n#user.fullname#\n#resetUrl#\n\n#server.http_host#\n#server.remote_addr#\n#settings.adminEmail#\n#settings.adminUrl#\n#settings.developerEmail#\n#settings.programName#");
INSERT INTO `#TABLE_PREFIX#__email_templates` VALUES("5","2023-10-24 20:53:32","0","2023-10-24 20:53:32","0","CMS-BGTASK-ERROR","Sent to admin when a scheduled task fails","#settings.adminEmail#","#settings.adminEmail#","#settings.developerEmail#","","","Scheduled tasks did not complete","<p>The following Scheduled Task did not complete successfully: </p>\r\n<table border=\"0\">\r\n<tbody>\r\n<tr><td>Date/Time</td><td> : </td><td>#bgtask.date#</td></tr>\r\n<tr><td>Activity</td><td> : </td><td>#bgtask.activity#</td></tr>\r\n<tr><td>Summary</td><td> : </td><td>#bgtask.summary#</td></tr>\r\n<tr><td>Completed</td><td> : </td><td>#bgtask.completed#</td></tr>\r\n<tr><td>Function</td><td> : </td><td>#bgtask.function#</td></tr>\r\n<tr><td>Output</td><td> : </td><td>#bgtask.output#</td></tr>\r\n<tr><td>Runtime</td><td> : </td><td>#bgtask.runtime# seconds</td></tr>\r\n</tbody>\r\n</table>\r\n<p>Please check the Scheduled Tasks logs here and check for additional errors:<br>#bgtasks.logsUrl#</p>\r\n<p>You can review the Scheduled Tasks status &amp; settings here: <br>#bgtasks.settingsUrl#</p>\r\n<p>*Please note, incomplete scheduled task alerts are only sent once an hour.</p>","#bgtask.date#\n#bgtask.activity#\n#bgtask.summary#\n#bgtask.completed#\n#bgtask.function#\n#bgtask.output#\n#bgtask.runtime#\n#bgtask.settingsUrl#\n#bgtask.logsUrl#\n\n#server.http_host#\n#server.remote_addr#\n#settings.adminEmail#\n#settings.adminUrl#\n#settings.developerEmail#\n#settings.programName#");
INSERT INTO `#TABLE_PREFIX#__email_templates` VALUES("6","2023-10-24 20:53:32","0","2023-10-24 20:53:32","0","CMS-ERRORLOG-ALERT","Sent to admin when a php error or warning is reported","#settings.adminEmail#","#settings.adminEmail#","#settings.developerEmail#","","","Errors reported on: #error.hostname#","<p>One or more php errors have been reported on:<strong> #error.hostname# (#error.servername#)</strong></p>\r\n<p>Check the error log for complete list and more details:<br><a href=\"#error.errorLogUrl#\">#error.errorLogUrl#</a></p>\r\n<p>Latest errors: </p>\r\n<p style=\"padding-left: 30px;\"><span style=\"color: #808080;\">#error.latestErrorsList#</span></p>\r\n<p><strong>*Note: Email notifications of new errors are only sent once an hour.</strong></p>","#error.hostname#\n#error.latestErrorsList#\n#error.errorLogUrl#\n\n#server.http_host#\n#server.remote_addr#\n#settings.adminEmail#\n#settings.adminUrl#\n#settings.developerEmail#\n#settings.programName#");

--
-- Table structure for table `#TABLE_PREFIX#__log_audit`
--

DROP TABLE IF EXISTS `#TABLE_PREFIX#__log_audit`;

CREATE TABLE `#TABLE_PREFIX#__log_audit` (
  `num` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dateLogged` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `audit_event` mediumtext COLLATE utf8mb4_unicode_ci,
  `event_source` mediumtext COLLATE utf8mb4_unicode_ci,
  `url` mediumtext COLLATE utf8mb4_unicode_ci,
  `user_cms` mediumtext COLLATE utf8mb4_unicode_ci,
  `user_web` mediumtext COLLATE utf8mb4_unicode_ci,
  `remote_addr` mediumtext COLLATE utf8mb4_unicode_ci,
  `http_user_agent` mediumtext COLLATE utf8mb4_unicode_ci,
  `additional_data` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


--
-- Dumping data for table `#TABLE_PREFIX#__log_audit`
--

INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("1","2023-10-24 21:30:56","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("2","2023-10-24 18:41:42","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("3","2023-10-24 18:49:00","Record added (sample_single_record)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"sample_single_record\",\"num\":1}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("4","2023-10-24 19:04:41","Record added (accounts)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"accounts\",\"num\":2}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("5","2023-10-24 19:05:16","Login: Failure","","https://2sistersandabrush:8890/cms/admin.php","","","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"lauren\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("6","2023-10-24 19:05:25","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("7","2023-10-24 19:08:27","Record updated  (accounts)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"accounts\",\"num\":\"2\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("8","2023-10-24 19:08:43","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("9","2023-10-24 19:09:16","Record updated  (accounts)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"accounts\",\"num\":\"2\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("10","2023-10-24 19:09:38","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"lauren\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("11","2023-10-24 19:09:47","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("12","2023-10-24 19:10:10","Record updated  (accounts)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"accounts\",\"num\":\"2\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("13","2023-10-24 19:10:24","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"lauren\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("14","2023-10-24 19:10:44","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("15","2023-10-24 19:27:27","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("16","2023-10-24 19:27:57","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("17","2023-10-24 19:28:25","Record updated  (accounts)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"accounts\",\"num\":\"2\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("18","2023-10-24 19:28:32","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"lauren\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("19","2023-10-24 19:28:54","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("20","2023-10-24 20:04:19","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("21","2023-10-24 20:53:15","Login: Failure","","https://2sistersandabrush:8890/cms/admin.php","","","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("22","2023-10-24 20:53:26","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("23","2023-10-24 21:29:39","Record added (accounts)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"accounts\",\"num\":3}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("24","2023-10-24 21:30:27","Record added (weddings)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"weddings\",\"num\":1}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("25","2023-10-24 21:43:32","Record updated  (weddings)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"weddings\",\"num\":\"1\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("26","2023-10-24 21:45:07","Record updated  (weddings)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"weddings\",\"num\":\"1\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("27","2023-10-24 22:01:17","Record added (weddings)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"weddings\",\"num\":2}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("28","2023-10-24 22:14:36","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"lauren\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("29","2023-10-24 22:15:00","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("30","2023-10-24 22:15:32","Record updated  (accounts)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"accounts\",\"num\":\"2\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("31","2023-10-24 22:15:39","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"lauren\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("32","2023-10-24 22:15:48","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"2sistersandabrush\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("33","2023-10-24 22:18:20","Login: Success","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 2\n    [username] => lauren\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"username\":\"lauren\"}");
INSERT INTO `#TABLE_PREFIX#__log_audit` VALUES("34","2023-10-24 22:19:29","Record updated  (accounts)","","https://2sistersandabrush:8890/cms/admin.php","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","Array\n(\n    [num] => 1\n    [username] => 2sistersandabrush\n    [_tableName] => accounts\n)\n","::1","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","{\"tableName\":\"accounts\",\"num\":\"2\"}");

--
-- Table structure for table `#TABLE_PREFIX#__media`
--

DROP TABLE IF EXISTS `#TABLE_PREFIX#__media`;

CREATE TABLE `#TABLE_PREFIX#__media` (
  `num` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `createdDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `createdByUserNum` int(10) unsigned NOT NULL,
  `updatedDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatedByUserNum` int(10) unsigned NOT NULL,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


--
-- Dumping data for table `#TABLE_PREFIX#__media`
--


--
-- Table structure for table `#TABLE_PREFIX#_accounts`
--

DROP TABLE IF EXISTS `#TABLE_PREFIX#_accounts`;

CREATE TABLE `#TABLE_PREFIX#_accounts` (
  `num` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `createdDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `createdByUserNum` int(10) unsigned NOT NULL,
  `updatedDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatedByUserNum` int(10) unsigned NOT NULL,
  `fullname` mediumtext COLLATE utf8mb4_unicode_ci,
  `email` mediumtext COLLATE utf8mb4_unicode_ci,
  `username` mediumtext COLLATE utf8mb4_unicode_ci,
  `password` mediumtext COLLATE utf8mb4_unicode_ci,
  `lastLoginDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expiresDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `neverExpires` tinyint(1) unsigned NOT NULL,
  `isAdmin` tinyint(1) unsigned NOT NULL,
  `disabled` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


--
-- Dumping data for table `#TABLE_PREFIX#_accounts`
--

INSERT INTO `#TABLE_PREFIX#_accounts` VALUES("1","2023-10-24 21:30:28","0","2023-10-24 21:30:28","0","2 Sisters & A Brush","jasonpberry78@gmail.com","2sistersandabrush","$sha1$d445373f7b2b2451d35938409ffab20ccbb12cfe","2023-10-24 22:30:54","0000-00-00 00:00:00","1","1","0");
INSERT INTO `#TABLE_PREFIX#_accounts` VALUES("2","2023-10-24 19:04:41","1","2023-10-24 22:19:29","1","Lauren K (edit","ldiesel45@gmail.com","lauren","$sha1$3f0700fba4c57bd36635c5969b50769fd0477a48","2023-10-24 22:28:30","2023-10-24 00:00:00","1","0","0");
INSERT INTO `#TABLE_PREFIX#_accounts` VALUES("3","2023-10-24 21:29:39","1","2023-10-24 21:29:39","1","Jane Bride","jane@email.com","jane","$sha1$297aa01b6229268ab629053c921288712022cef2","0000-00-00 00:00:00","2023-10-24 00:00:00","1","0","0");

--
-- Table structure for table `#TABLE_PREFIX#_single_record_sample`
--

DROP TABLE IF EXISTS `#TABLE_PREFIX#_single_record_sample`;

CREATE TABLE `#TABLE_PREFIX#_single_record_sample` (
  `num` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `createdDate` datetime NOT NULL,
  `createdByUserNum` int(10) unsigned NOT NULL,
  `updatedDate` datetime NOT NULL,
  `updatedByUserNum` int(10) unsigned NOT NULL,
  `title` mediumtext COLLATE utf8mb4_unicode_ci,
  `content` mediumtext COLLATE utf8mb4_unicode_ci,
  `date` datetime NOT NULL,
  `status` mediumtext COLLATE utf8mb4_unicode_ci,
  `checked` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Dumping data for table `#TABLE_PREFIX#_single_record_sample`
--

INSERT INTO `#TABLE_PREFIX#_single_record_sample` VALUES("1","2015-10-13 12:39:52","1","2015-10-13 13:33:53","1","Sample Record Title","<p>Eshorta eshekitathe ofe sodawtaw afegel coosona tirolig ofedadal rere arisi cerise raheror athel forseja awtothe rolorthel jafe cutut lonurnap tam ethumen efeth newebot ehoira isado ujemorv rekedeh ahiluse damor ehe arethe torenof esiturge lishek sisute itabo rabikomus ene chethutec sheti losoumaf nulesid botefour relala enabi hasisaseb exe ralenob agebaw teces bedut ni bereleh shoigu enatouto enasadou eroikegac hithu farupi toset ubaras awhopu tecul udile ahawcuk sare atafar loleh ihet ciduthi aser atebe ichin luta nihot.</p>","2015-10-13 00:00:00","Approved","1");

--
-- Table structure for table `#TABLE_PREFIX#_uploads`
--

DROP TABLE IF EXISTS `#TABLE_PREFIX#_uploads`;

CREATE TABLE `#TABLE_PREFIX#_uploads` (
  `num` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order` int(10) unsigned NOT NULL,
  `createdTime` datetime NOT NULL,
  `tableName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fieldName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recordNum` int(11) DEFAULT NULL,
  `filePath` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urlPath` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` int(10) unsigned NOT NULL,
  `height` int(10) unsigned NOT NULL,
  `filesize` mediumtext COLLATE utf8mb4_unicode_ci,
  `preSaveTempId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mediaNum` mediumtext COLLATE utf8mb4_unicode_ci,
  `thumbFilePath` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbUrlPath` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbWidth` int(10) unsigned NOT NULL,
  `thumbHeight` int(10) unsigned NOT NULL,
  `thumbFilePath2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbUrlPath2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbWidth2` int(10) unsigned NOT NULL,
  `thumbHeight2` int(10) unsigned NOT NULL,
  `thumbFilePath3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbUrlPath3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbWidth3` int(10) unsigned NOT NULL,
  `thumbHeight3` int(10) unsigned NOT NULL,
  `thumbFilePath4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbUrlPath4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbWidth4` int(10) unsigned NOT NULL,
  `thumbHeight4` int(10) unsigned NOT NULL,
  `info1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `info2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `info3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `info4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `info5` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


--
-- Dumping data for table `#TABLE_PREFIX#_uploads`
--


--
-- Table structure for table `#TABLE_PREFIX#_weddings`
--

DROP TABLE IF EXISTS `#TABLE_PREFIX#_weddings`;

CREATE TABLE `#TABLE_PREFIX#_weddings` (
  `num` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `createdDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `createdByUserNum` int(10) unsigned NOT NULL,
  `updatedDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatedByUserNum` int(10) unsigned NOT NULL,
  `dragSortOrder` int(10) unsigned NOT NULL,
  `wedding_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `client_name` mediumtext COLLATE utf8mb4_unicode_ci,
  `venue_name` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Dumping data for table `#TABLE_PREFIX#_weddings`
--

INSERT INTO `#TABLE_PREFIX#_weddings` VALUES("1","2023-10-24 21:30:27","1","2023-10-24 21:45:07","1","20","2024-11-05 00:00:00","3","Venue Name Here");
INSERT INTO `#TABLE_PREFIX#_weddings` VALUES("2","2023-10-24 22:01:17","1","2023-10-24 22:01:17","1","10","2023-10-24 00:00:00","2","Venue 2");

-- Dump completed on 2023-10-24 22:31:32 -0400

