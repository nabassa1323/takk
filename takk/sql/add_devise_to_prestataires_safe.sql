-- Script SQL sécurisé pour ajouter la colonne devise
-- Ce script vérifie d'abord si la colonne existe avant de l'ajouter

USE c1498480c_mariage;

-- Vérifier si la colonne existe et l'ajouter si nécessaire
SET @dbname = DATABASE();
SET @tablename = 'prestataires';
SET @columnname = 'devise';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1', -- Colonne existe déjà
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(10) DEFAULT ''EUR''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Mettre à jour les prestataires existants selon leur pays
UPDATE prestataires 
SET devise = CASE 
    WHEN pays = 'Sénégal' THEN 'XOF'
    WHEN pays = 'France' THEN 'EUR'
    ELSE 'EUR'
END
WHERE devise IS NULL OR devise = '';
