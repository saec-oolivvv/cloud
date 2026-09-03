-- ══════════════════════════════════════════════════════════════
-- SAEC Cloud — Add Plan & Billing Fields to Tenants
-- Exécuter pour ajouter les champs plan et facturation
-- ══════════════════════════════════════════════════════════════

USE saec_cloud;

ALTER TABLE tenants 
ADD COLUMN plan ENUM('starter', 'professional', 'enterprise', 'custom') DEFAULT 'custom' AFTER slug,
ADD COLUMN plan_max_users INT UNSIGNED DEFAULT 0 AFTER max_users,
ADD COLUMN extra_users_count INT UNSIGNED DEFAULT 0 AFTER plan_max_users,
ADD COLUMN extra_user_price DECIMAL(10,2) DEFAULT 0.00 AFTER extra_users_count,
ADD COLUMN billing_cycle ENUM('monthly', 'yearly') DEFAULT 'monthly' AFTER extra_user_price,
ADD COLUMN billing_status ENUM('active', 'trial', 'past_due', 'cancelled') DEFAULT 'trial' AFTER billing_cycle,
ADD COLUMN trial_ends_at TIMESTAMP NULL AFTER billing_status;

-- Plans par défaut
-- Starter: 1 user, extra_user_price = 2.00
-- Professional: 5 users, extra_user_price = 1.50
-- Enterprise: 15 users, extra_user_price = 1.00