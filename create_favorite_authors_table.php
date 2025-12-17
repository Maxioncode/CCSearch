<?php
include "database/database.php";

$sql = "
CREATE TABLE IF NOT EXISTS `favorite_authors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` varchar(20) NOT NULL,
  `authorID` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorite` (`studentID`, `authorID`),
  KEY `fk_favorite_student` (`studentID`),
  KEY `fk_favorite_author` (`authorID`),
  CONSTRAINT `fk_favorite_student` FOREIGN KEY (`studentID`) REFERENCES `registration` (`studentID`) ON DELETE CASCADE,
  CONSTRAINT `fk_favorite_author` FOREIGN KEY (`authorID`) REFERENCES `registration` (`studentID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
";

if ($conn->query($sql) === TRUE) {
    echo "Table favorite_authors created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>



