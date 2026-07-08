# Silverstripe CMS 6 Upgrade Guide

This document outlines the necessary changes to upgrade this module to Silverstripe CMS 6. Review these changes carefully before upgrading your project.

## ⚠️ BREAKING CHANGE: New Requirements

Your `composer.json` file must be updated to meet the new requirements for Silverstripe 6.

-   **`sunnysideup/ecommerce`**: Update constraint to `^33.0`
-   **`silverstripe/recipe-cms`**: Update constraint to `^6.0`

## 🚨 CRITICAL REVIEW REQUIRED / RISKY: Pending Dependencies

The following dependency could not be updated automatically. You must review it and find a compatible version or replacement.

-   **`sunnysideup/flush`**: The upgrade process noted that no compatible stable release was available for this dependency. **You will need to manually resolve this dependency in your `composer.json`.**

## ⚠️ BREAKING CHANGE: Configuration Updates

Configuration files have been updated to use new class names.

-   **Database Admin Class**: The deprecated `SilverStripe\ORM\DatabaseAdmin` has been replaced with `SilverStripe\Dev\DbBuild` in `_config/database.legacy.yml`. Update any custom configurations that reference the old class.

## API Changes

To ensure compatibility with Silverstripe CMS 6, method signatures in several classes have been updated with the `#[Override]` attribute. This change is non-breaking but indicates that these methods now explicitly override parent class methods.

This attribute has been added to methods in the following classes:
-   `Sunnysideup\EcommerceAdvanceRetailConnector\Model\Process\OrderStatusLogSendOrderToAdvanceRetail`
-   `Sunnysideup\EcommerceAdvanceRetailConnector\Model\Process\OrderStepSendOrderToAdvanceRetail`
