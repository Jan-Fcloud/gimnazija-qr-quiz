<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

function db_setup(){
    global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . "NFC_Hunt_Dijak";
    $table_name2 = $wpdb->prefix . "NFC_Hunt_Naloga";
	$table_name3 = $wpdb->prefix . "NFC_Hunt_Odgovori";

    $sql = "CREATE TABLE `$table_name` (
        `ID_DIJAK` INT NOT NULL AUTO_INCREMENT,
        `Tocke` INT(2) NOT NULL DEFAULT 0,
        `Cas_Registracije` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `FK_UserID` BIGINT UNSIGNED,
        PRIMARY KEY (`ID_DIJAK`),
        CONSTRAINT FK_dijakUnD FOREIGN KEY (FK_UserID) REFERENCES wp_users(ID) ON UPDATE CASCADE ON DELETE CASCADE
    )ENGINE=MyISAM $charset_collate;

    CREATE TABLE `$table_name2` (
        `ID_NALOGA` INT NOT NULL AUTO_INCREMENT,
        `Vprasanje` VARCHAR(250) NOT NULL,
        `Tip` ENUM('Text', 'Radio', 'Number', 'Checkbox', 'Select', 'Upload', 'Scan') NOT NULL DEFAULT 'Text',
        `Odgovor` VARCHAR(250) NOT NULL,
        `Tocke` INT(2) NOT NULL DEFAULT 1,
        `Permalink` VARCHAR(25) NOT NULL,
        `FK_PostID` BIGINT UNSIGNED,
        PRIMARY KEY (`ID_NALOGA`),
        CONSTRAINT FK_nalogaUnP FOREIGN KEY (FK_PostID) REFERENCES wp_posts(ID) ON UPDATE CASCADE ON DELETE CASCADE
    )ENGINE=MyISAM $charset_collate;

	CREATE TABLE `$table_name3`(
	    `ID_ODGOVOR` INT NOT NULL AUTO_INCREMENT,
	    `FK_DijakID` INT NOT NULL,
	    `FK_NalogaID` INT NOT NULL,
	    `Odgovor` VARCHAR(250) NOT NULL,
	    `Pravilno` BOOLEAN NOT NULL DEFAULT 0,
	    PRIMARY KEY (`ID_ODGOVOR`),
	    CONSTRAINT FK_odgovorUnD FOREIGN KEY (FK_DijakID) REFERENCES $table_name(ID_DIJAK) ON UPDATE CASCADE ON DELETE CASCADE,
	    CONSTRAINT FK_odgovorUnN FOREIGN KEY (FK_NalogaID) REFERENCES $table_name2(ID_NALOGA) ON UPDATE CASCADE ON DELETE CASCADE
	)ENGINE=MyISAM $charset_collate;


"; // Foreign key is connected to the actual user, and the entry is deleted if the user is deleted
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql);
}
