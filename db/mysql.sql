# $Id: mysql.sql,v 1.2 2006/08/28 16:41:20 mark-nielsen Exp $
# This file contains a complete database schema for all the 
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data 
# that may be used, especially new entries in the table log_display


CREATE TABLE mdl_realtimequiz (
    id BIGINT(10) unsigned NOT NULL auto_increment,
    course BIGINT(10) unsigned NOT NULL DEFAULT 0,
    name VARCHAR(255) NOT NULL DEFAULT '',
    status SMALLINT(3) unsigned NOT NULL DEFAULT 0,
    currentquestion BIGINT(10) unsigned NOT NULL DEFAULT 0,
    nextendtime BIGINT(10) unsigned NOT NULL DEFAULT 0,
    currentsessionid BIGINT(10) unsigned NOT NULL DEFAULT 0,
    questiontime SMALLINT(3) unsigned NOT NULL DEFAULT 30,
    classresult SMALLINT(3) unsigned NOT NULL DEFAULT 0,
    questionresult SMALLINT(3) unsigned NOT NULL DEFAULT 0,
CONSTRAINT  PRIMARY KEY (id)
);

ALTER TABLE mdl_realtimequiz COMMENT='Defines realtime quizzes';

CREATE TABLE mdl_realtimequiz_question (
    id BIGINT(10) NOT NULL auto_increment,
    quizid BIGINT(10) unsigned NOT NULL DEFAULT 0,
    questionnum SMALLINT(3) unsigned NOT NULL DEFAULT 0,
    questiontext VARCHAR(255) NOT NULL DEFAULT '',
    questiontime SMALLINT(4) unsigned DEFAULT 0,
CONSTRAINT  PRIMARY KEY (id)
);

ALTER TABLE mdl_realtimequiz_question COMMENT='Defines questions for the realtime quizzes';

CREATE TABLE mdl_realtimequiz_answer (
    id BIGINT(10) NOT NULL auto_increment,
    questionid BIGINT(10) unsigned NOT NULL DEFAULT 0,
    answertext VARCHAR(255) NOT NULL DEFAULT '',
    correct TINYINT(2) unsigned NOT NULL DEFAULT 0,
CONSTRAINT  PRIMARY KEY (id)
);

ALTER TABLE mdl_realtimequiz_answer COMMENT='The available answers for each question in the realtime quiz';

CREATE TABLE mdl_realtimequiz_submitted (
    id BIGINT(10) NOT NULL auto_increment,
    questionid BIGINT(10) unsigned NOT NULL DEFAULT 0,
    sessionid BIGINT(10) unsigned DEFAULT 0,
    userid BIGINT(10) unsigned NOT NULL DEFAULT 0,
    answerid BIGINT(10) unsigned NOT NULL DEFAULT 0,
CONSTRAINT  PRIMARY KEY (id)
);

ALTER TABLE mdl_realtimequiz_submitted COMMENT='The answers that have been submitted by students';

CREATE TABLE mdl_realtimequiz_session (
    id BIGINT(10) NOT NULL auto_increment,
    name VARCHAR(255) NOT NULL DEFAULT '',
    quizid BIGINT(10) unsigned NOT NULL DEFAULT 0,
    timestamp BIGINT(10) unsigned DEFAULT 0,
CONSTRAINT  PRIMARY KEY (id)
);

ALTER TABLE mdl_realtimequiz_session COMMENT='Details about each quiz session';