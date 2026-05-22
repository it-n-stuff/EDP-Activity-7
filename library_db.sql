-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: library_db
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `author`
--

DROP TABLE IF EXISTS `author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `author` (
  `AuthorID` int NOT NULL AUTO_INCREMENT,
  `Author_FN` varchar(50) NOT NULL,
  `Author_LN` varchar(50) NOT NULL,
  PRIMARY KEY (`AuthorID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author`
--

LOCK TABLES `author` WRITE;
/*!40000 ALTER TABLE `author` DISABLE KEYS */;
INSERT INTO `author` VALUES (1,'Brian','Kernighan'),(2,'Dennis','Ritchie'),(3,'Erich','Gamma'),(4,'Robert','Martin'),(5,'Thomas','Cormen'),(6,'Joshua','Bloch'),(7,'Abraham','Silberschatz'),(8,'Mark','Lutz'),(9,'Andrew','Tanenbaum');
/*!40000 ALTER TABLE `author` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `BeforeDeleteAuthor` BEFORE DELETE ON `author` FOR EACH ROW BEGIN
	-- TRIGGER: runs BEFORE an author record is deleted
	-- This trigger automatically deletes all records in book_author 
	-- that reference the author being removed
    
	-- Deletes the row in the book_author table with the same 
	-- AuthorID that is about to be deleted from the author table
	DELETE FROM book_author
    WHERE AuthorID = OLD.AuthorID;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `book`
--

DROP TABLE IF EXISTS `book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book` (
  `BookID` int NOT NULL AUTO_INCREMENT,
  `ISBN` varchar(50) NOT NULL,
  `Title` varchar(100) NOT NULL,
  `Publisher` varchar(100) NOT NULL,
  `PublicationYear` int NOT NULL,
  PRIMARY KEY (`BookID`),
  UNIQUE KEY `ISBN_UNIQUE` (`ISBN`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book`
--

LOCK TABLES `book` WRITE;
/*!40000 ALTER TABLE `book` DISABLE KEYS */;
INSERT INTO `book` VALUES (1,'9780131103627','The C Programming Language','Prentice Hall',1988),(2,'9780201633610','Design Patterns','Addison-Wesley',1994),(3,'9780132350884','Clean Code','Prentice Hall',2008),(4,'9780262033848','Introduction to Algorithms','MIT Press',2009),(5,'9780134685991','Effective Java','Addison-Wesley',2018),(6,'9780073523323','Database System Concepts','McGraw-Hill',2011),(7,'9781491950357','Learning Python','OReilly Media',2013),(8,'9780134494166','Computer Networks','Pearson',2017),(9,'9781119456339','Data Science for Business','Wiley',2013),(10,'9780133970777','Software Engineering','Pearson',2014);
/*!40000 ALTER TABLE `book` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_author`
--

DROP TABLE IF EXISTS `book_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_author` (
  `BookID` int NOT NULL,
  `AuthorID` int NOT NULL,
  PRIMARY KEY (`BookID`,`AuthorID`),
  KEY `AuthorID_idx` (`AuthorID`),
  CONSTRAINT `fk_author` FOREIGN KEY (`AuthorID`) REFERENCES `author` (`AuthorID`),
  CONSTRAINT `fk_book` FOREIGN KEY (`BookID`) REFERENCES `book` (`BookID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_author`
--

LOCK TABLES `book_author` WRITE;
/*!40000 ALTER TABLE `book_author` DISABLE KEYS */;
INSERT INTO `book_author` VALUES (1,1),(1,2),(2,3),(3,4),(4,5),(5,6),(6,7),(7,8),(8,9);
/*!40000 ALTER TABLE `book_author` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `borrow`
--

DROP TABLE IF EXISTS `borrow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `borrow` (
  `BorrowID` int NOT NULL AUTO_INCREMENT,
  `StudentID` int NOT NULL,
  `BookID` int NOT NULL,
  `BorrowDate` date NOT NULL,
  `ReturnDate` date DEFAULT NULL,
  PRIMARY KEY (`BorrowID`),
  KEY `BookID_idx` (`BookID`),
  KEY `StudentID_idx` (`StudentID`),
  CONSTRAINT `fk_borrow_book` FOREIGN KEY (`BookID`) REFERENCES `book` (`BookID`),
  CONSTRAINT `fk_borrow_student` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `borrow`
--

LOCK TABLES `borrow` WRITE;
/*!40000 ALTER TABLE `borrow` DISABLE KEYS */;
INSERT INTO `borrow` VALUES (1,1,3,'2026-01-10','2026-01-17'),(2,2,5,'2026-01-12','2026-01-20'),(3,3,1,'2026-01-15','2026-01-21'),(4,4,7,'2026-01-18','2026-01-25'),(5,5,2,'2026-01-20','2026-05-09'),(6,6,4,'2026-01-22','2026-01-30'),(7,7,6,'2026-01-25',NULL),(8,8,8,'2026-01-27','2026-02-03'),(9,9,9,'2026-02-01',NULL),(10,10,10,'2026-02-05','2026-02-12'),(11,3,3,'2026-05-09',NULL);
/*!40000 ALTER TABLE `borrow` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `BeforeInsertBorrow` BEFORE INSERT ON `borrow` FOR EACH ROW BEGIN
	-- TRIGGER: runs BEFORE a borrow record is inserted
	-- This trigger prevents a student from borrowing
	-- a book that is still not returned by another student 
	DECLARE BookBorrowed INT;
    
    -- Check if the book is currently borrowed
    SELECT COUNT(*)
    INTO BookBorrowed
    FROM borrow
    WHERE BookID = NEW.BookID
    AND ReturnDate IS NULL;
    
    -- If book is already borrowed, stop INSERT
    IF bookBorrowed > 0 THEN
		SET NEW.BookID = NULL; 
	END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `BeforeUpdateBorrow` BEFORE UPDATE ON `borrow` FOR EACH ROW BEGIN
	-- TRIGGER: runs BEFORE a borrow record is updated
	-- This trigger ensures the return date is valid.
	-- A book cannot be returned earlier than it was borrowed.
    
	-- Check if the new return date to be entered is earlier than the borrow date
	IF NEW.ReturnDate IS NOT NULL
	AND NEW.ReturnDate < OLD.BorrowDate THEN
		
        -- Cancel UPDATE by restoring old value
		SET NEW.ReturnDate = OLD.ReturnDate;
	END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary view structure for view `currentlyborrowedbooks`
--

DROP TABLE IF EXISTS `currentlyborrowedbooks`;
/*!50001 DROP VIEW IF EXISTS `currentlyborrowedbooks`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `currentlyborrowedbooks` AS SELECT 
 1 AS `BorrowID`,
 1 AS `StudentNumber`,
 1 AS `StudentName`,
 1 AS `BookTitle`,
 1 AS `BorrowDate`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `mostpopularbooks`
--

DROP TABLE IF EXISTS `mostpopularbooks`;
/*!50001 DROP VIEW IF EXISTS `mostpopularbooks`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `mostpopularbooks` AS SELECT 
 1 AS `BookTitle`,
 1 AS `TimesBorrowed`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student` (
  `StudentID` int NOT NULL AUTO_INCREMENT,
  `StudentNumber` int NOT NULL,
  `Student_FN` varchar(50) NOT NULL,
  `Student_LN` varchar(50) NOT NULL,
  `YearLevel` int NOT NULL,
  `Course` varchar(50) NOT NULL,
  PRIMARY KEY (`StudentID`),
  UNIQUE KEY `StudentNumber_UNIQUE` (`StudentNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student`
--

LOCK TABLES `student` WRITE;
/*!40000 ALTER TABLE `student` DISABLE KEYS */;
INSERT INTO `student` VALUES (1,20240001,'Juan','Dela Cruz',1,'BS Information Technology'),(2,20240002,'Maria','Santos',2,'BS Information Technology'),(3,20240003,'Jose','Reyes',3,'BS Nursing'),(4,20240004,'Ana','Lopez',1,'BS Business Administration'),(5,20240005,'Pedro','Garcia',4,'BS Information Technology'),(6,20240006,'Luisa','Torres',2,'BS Secondary Education'),(7,20240007,'Carlo','Gomez',3,'BS Computer Science'),(8,20240008,'Angela','Rivera',1,'BS Nursing'),(9,20240009,'Mark','Fernandez',2,'BS Business Administration'),(10,20240010,'Sofia','Morales',4,'BS Computer Science');
/*!40000 ALTER TABLE `student` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `studentborrowhistory`
--

DROP TABLE IF EXISTS `studentborrowhistory`;
/*!50001 DROP VIEW IF EXISTS `studentborrowhistory`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `studentborrowhistory` AS SELECT 
 1 AS `StudentNumber`,
 1 AS `StudentName`,
 1 AS `BookTitle`,
 1 AS `BorrowDate`,
 1 AS `ReturnDate`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `UserID` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Username_UNIQUE` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'Cherie Annaliese','Penrose','admin','admin','$2y$10$Kr5XLN4vwK9v5MqKbrMwSuWPLJ1cIaFxwdDebs0ni7epSFuojIbAe','dearpenrose@gmail.com','active'),(2,'Mheea','Rose','staff','staff1','$2y$10$iIam4cIbbOS/C3dPoQ7qp.D.4qQBfYzQHgoZQwbjIP6yqMCSQCazi',NULL,'active'),(3,'Eve','Rosenberg','staff','staff2','$2y$10$b/Af47eO7XUidQNWFUb6jOruj6c/ZIyv.la6uWN1h4cem0R7GHXCe',NULL,'active');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'library_db'
--

--
-- Dumping routines for database 'library_db'
--
/*!50003 DROP FUNCTION IF EXISTS `FullName` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `FullName`(FirstName CHAR(50), LastName CHAR(50)) RETURNS char(100) CHARSET utf8mb4
    DETERMINISTIC
RETURN concat(FirstName, ' ', LastName) ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ReturnBook` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `ReturnBook`(IN P_BorrowID INT, IN P_ReturnDate DATE)
BEGIN
	UPDATE borrow br
    SET ReturnDate = P_ReturnDate
    WHERE BorrowID = P_BorrowID;
    
    SELECT 
		br.BorrowID, 
        FullName(s.Student_FN, s.Student_LN) AS StudentName,
        Title AS BookTitle,
        br.BorrowDate,
        br.ReturnDate
	FROM borrow br
    JOIN student s ON br.StudentID = s.StudentID
    JOIN book b ON br.BookID = b.BookID;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `currentlyborrowedbooks`
--

/*!50001 DROP VIEW IF EXISTS `currentlyborrowedbooks`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `currentlyborrowedbooks` AS select 1 AS `BorrowID`,1 AS `StudentNumber`,1 AS `StudentName`,1 AS `BookTitle`,1 AS `BorrowDate` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `mostpopularbooks`
--

/*!50001 DROP VIEW IF EXISTS `mostpopularbooks`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `mostpopularbooks` AS select 1 AS `BookTitle`,1 AS `TimesBorrowed` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `studentborrowhistory`
--

/*!50001 DROP VIEW IF EXISTS `studentborrowhistory`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `studentborrowhistory` AS select `s`.`StudentNumber` AS `StudentNumber`,`FullName`(`s`.`Student_FN`,`s`.`Student_LN`) AS `StudentName`,`b`.`Title` AS `BookTitle`,`br`.`BorrowDate` AS `BorrowDate`,`br`.`ReturnDate` AS `ReturnDate` from ((`borrow` `br` join `student` `s` on((`br`.`StudentID` = `s`.`StudentID`))) join `book` `b` on((`br`.`BookID` = `b`.`BookID`))) order by `br`.`BorrowDate` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-22 12:58:57
