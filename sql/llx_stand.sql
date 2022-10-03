-- ============================================================================
-- Copyright (C) 2019 Mikael Carlavan  <contact@mika-carl.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================

DROP TABLE `llx_stand`;

CREATE TABLE IF NOT EXISTS `llx_stand`
(
    `rowid`          int(11) AUTO_INCREMENT,
    `ref`            varchar(255) NULL,
    `name`           varchar(255) NULL,
    `description`    text         NULL,
    `address`        varchar(255) NULL,
    `zip`            varchar(255) NULL,
    `town`           varchar(255) NULL,
    `longitude`      double  DEFAULT 0,
    `latitude`       double  DEFAULT 0,
    `datec`          datetime     NULL,
    `active`         int(11) DEFAULT 0,
    `user_author_id` int(11) DEFAULT 0,
    `entity`         int(11) DEFAULT 0,
    `tms`            timestamp    NOT NULL,
    PRIMARY KEY (`rowid`)
) ENGINE = innodb
  DEFAULT CHARSET = utf8;

