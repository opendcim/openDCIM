-- HDD proof of destruction support
ALTER TABLE fac_HDD ADD COLUMN ProofFile VARCHAR(255) DEFAULT NULL AFTER TypeMedia;

-- Config parameter for HDD proofs base path (with trailing slash)
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal)
SELECT 'hdd_proof_path','assets/files/hdd/','path','string','assets/files/hdd/'
WHERE NOT EXISTS (SELECT 1 FROM fac_Config WHERE Parameter='hdd_proof_path');
