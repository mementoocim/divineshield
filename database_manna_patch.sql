-- ============================================================
-- DivineShield - MannaPack Inventory System Patch
-- Run this once in phpMyAdmin against divineshield_db
-- ============================================================

-- --------------------------------------------------------
-- Table: manna_restock_log
-- Records every batch of MannaPack packs received from a donor
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `manna_restock_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `added_by` int(11) NOT NULL,
  `donor_name` varchar(150) NOT NULL,
  `quantity_added` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `added_by` (`added_by`),
  CONSTRAINT `restock_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: manna_distribution_log
-- Records every distribution event per church site / barangay
-- quantity is manually entered by admin (flexible — can include DQ children)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `manna_distribution_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `church_site_id` int(11) NOT NULL,
  `distributed_by` int(11) NOT NULL,
  `total_children` int(11) NOT NULL DEFAULT 0,
  `qualified_children_count` int(11) NOT NULL DEFAULT 0,
  `disqualified_children_count` int(11) NOT NULL DEFAULT 0,
  `packs_distributed` int(11) NOT NULL,
  `stock_before` int(11) NOT NULL,
  `stock_after` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `distributed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `church_site_id` (`church_site_id`),
  KEY `distributed_by` (`distributed_by`),
  CONSTRAINT `distrib_ibfk_1` FOREIGN KEY (`church_site_id`) REFERENCES `church_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `distrib_ibfk_2` FOREIGN KEY (`distributed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
