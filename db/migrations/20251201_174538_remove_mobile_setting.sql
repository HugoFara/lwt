-- Migration: Remove deprecated mobile display mode setting
-- Date: 2025-12-01
-- Description: Removes the set-mobile-display-mode setting as the mobile feature has been deprecated

DELETE FROM settings WHERE StKey = 'set-mobile-display-mode';
