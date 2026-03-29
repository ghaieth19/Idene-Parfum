START TRANSACTION;

USE idene_parfum;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'idene_parfum'
      AND TABLE_NAME = 'perfume_catalog'
      AND COLUMN_NAME = 'price_dzd'
);

SET @alter_sql := IF(
    @column_exists = 0,
    'ALTER TABLE perfume_catalog ADD COLUMN price_dzd DECIMAL(10,2) NULL AFTER source_label',
    'SELECT 1'
);

PREPARE stmt FROM @alter_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE perfume_catalog
SET price_dzd = 27.00
WHERE catalog_group = 'PRINCIPAL';

UPDATE perfume_catalog
SET price_dzd = 15.00
WHERE catalog_group = 'SMART';

UPDATE perfume_catalog
SET price_dzd = 8.50
WHERE catalog_group = 'ENFANT';

COMMIT;
