DROP TABLE IF EXISTS `obo-test`.`Contacts`;
CREATE TABLE `obo-test`.`Contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address` int(11) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test`.`Contacts` (`id`, `email`, `phone`, `address`) VALUES (1, 'john@doe.com', '+420123456789', 1);
INSERT INTO `obo-test`.`Contacts` (`id`, `email`, `phone`, `address`) VALUES (2, 'john@doe.com', '+420123456789', 1);
INSERT INTO `obo-test`.`Contacts` (`id`, `email`, `phone`, `address`) VALUES (3, 'john@doe.com', '+420123456789', 1);


DROP TABLE IF EXISTS `obo-test`.`PersonalContacts`;
CREATE TABLE `obo-test`.`PersonalContacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `surname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test`.`PersonalContacts` (`id`, `firstname`, `surname`) VALUES (1, 'John', 'Doe');
INSERT INTO `obo-test`.`PersonalContacts` (`id`, `firstname`, `surname`) VALUES (2, 'John', 'Doe');


DROP TABLE IF EXISTS `obo-test`.`BusinessContacts`;
CREATE TABLE `obo-test`.`BusinessContacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `companyName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test`.`BusinessContacts` (`id`, `companyName`) VALUES (1, 'My company s.r.o.');


DROP TABLE IF EXISTS `obo-test2`.`Contacts`;
CREATE TABLE `obo-test2`.`Contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fax` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test2`.`Contacts` (`id`, `fax`) VALUES (1, '+420987654321');
INSERT INTO `obo-test2`.`Contacts` (`id`, `fax`) VALUES (2, '+420987654321');


DROP TABLE IF EXISTS `obo-test2`.`Address`;
CREATE TABLE `obo-test2`.`Address` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `owner` INT(11) NOT NULL,
  `ownerEntity` VARCHAR(255) NOT NULL,
  `street` VARCHAR(255) NOT NULL,
  `houseNumber` VARCHAR(255) NOT NULL,
  `town` VARCHAR(255) NOT NULL,
  `postalCode` int(5) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test2`.`Address` (`id`, `owner`, `ownerEntity`, `street`, `houseNumber`, `town`, `postalCode`) VALUES (1, 1, 'obo\\DataStorage\\Tests\\Assets\\Entities\\Contact\\Personal', 'My Street', '123', 'My Town', '12345');
INSERT INTO `obo-test2`.`Address` (`id`, `owner`, `ownerEntity`, `street`, `houseNumber`, `town`, `postalCode`) VALUES (2, 2, 'obo\\DataStorage\\Tests\\Assets\\Entities\\Contact\\Personal', 'My Street', '123', 'My Town', '12345');
INSERT INTO `obo-test2`.`Address` (`id`, `owner`, `ownerEntity`, `street`, `houseNumber`, `town`, `postalCode`) VALUES (3, 3, 'obo\\DataStorage\\Tests\\Assets\\Entities\\Contact\\Company', 'My Street', '123', 'My Town', '12345');

DROP TABLE IF EXISTS `obo-test2`.`RelationshipBetweenContactAndOtherAddresses`;
CREATE TABLE `obo-test2`.`RelationshipBetweenContactAndOtherAddresses` (
  `Contacts` INT NOT NULL,
  `Address` INT NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test2`.`RelationshipBetweenContactAndOtherAddresses` (`Contacts`, `Address`) VALUES (1, 1);
INSERT INTO `obo-test2`.`RelationshipBetweenContactAndOtherAddresses` (`Contacts`, `Address`) VALUES (2, 2);
INSERT INTO `obo-test2`.`RelationshipBetweenContactAndOtherAddresses` (`Contacts`, `Address`) VALUES (3, 3);
