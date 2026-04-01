USE idene_parfum;

ALTER TABLE stock
    ADD COLUMN IF NOT EXISTS raw_material_quantity_ml DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER min_alert_ml,
    ADD COLUMN IF NOT EXISTS raw_material_min_alert_ml DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER raw_material_quantity_ml;
