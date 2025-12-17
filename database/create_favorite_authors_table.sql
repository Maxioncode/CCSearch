-- Create favorite_authors table
CREATE TABLE IF NOT EXISTS `favorite_authors` (
  `favoriteID` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` varchar(20) NOT NULL,
  `favorite_studentID` varchar(20) NOT NULL,
  `added_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`favoriteID`),
  UNIQUE KEY `unique_favorite` (`studentID`, `favorite_studentID`),
  KEY `fk_favorite_student` (`studentID`),
  KEY `fk_favorite_author` (`favorite_studentID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;