-- Ajouter la colonne devise à la table prestataires
-- Note: Si la colonne existe déjà, cette commande générera une erreur, ce qui est normal

USE c1498480c_mariage;

-- Ajouter la colonne devise
ALTER TABLE prestataires 
ADD COLUMN devise VARCHAR(10) DEFAULT 'EUR';

-- Mettre à jour les prestataires existants selon leur pays
UPDATE prestataires 
SET devise = CASE 
    WHEN pays = 'Sénégal' THEN 'XOF'
    WHEN pays = 'France' THEN 'EUR'
    ELSE 'EUR'
END
WHERE devise IS NULL OR devise = '';
