# Disposal Processing Workflow

## Overview (English)
The `DisposalProcessing.php` page, available under **Bulk Importer > Process Scrap**, guides Site Administrators through an automated disposal workflow using the logistics Excel file. It imports serial numbers, classifies devices into four categories, offers per-category actions, and records every step for auditing/rollback.

## Vue d’ensemble (Français)
La page `DisposalProcessing.php`, accessible via **Bulk Importer > Process Scrap**, accompagne les administrateurs de site dans un workflow automatisé de traitement du rebut à partir du fichier Excel logistique. Elle importe les numéros de série, classe les équipements en quatre catégories, propose les actions adaptées et journalise chaque étape avec option de rollback.

---

## Expected File (EN)
- **Format**: Excel XLSX/XLS (one per run).
- **Source**: Logistics spreadsheet listing devices slated for disposal.
- **Pivot Column**: `Serial/No` (or similar). Only this column is used—others are ignored.
- Recommendation: ensure serials exist in OpenDCIM to minimize minimal-entry creation.

## Fichier attendu (FR)
- **Format** : Excel XLSX/XLS (un par session).
- **Origine** : Fichier logistique listant les équipements destinés à la destruction/recyclage.
- **Colonne pivot** : `Serial/No` (ou équivalente). Seule cette colonne est utilisée, les autres sont ignorées.
- Recommandation : vérifier que les numéros sont présents dans OpenDCIM afin de limiter les créations minimales.

---

## Workflow Steps (EN)
1. **Upload** the Excel file; headers can be detected up to row 20.  
2. **Select** the worksheet tabs to include and confirm/adjust the detected Serial Number column (auto-mapped to labels like “SN”, “Serial Number”, etc.).  
3. **Categorize** devices:
   - Category 1: unknown serials (not in DB).
   - Category 2: devices still installed (Cabinet > 0).
   - Category 3: devices in Storage Room (Cabinet = -1).
   - Category 4: already disposed (Cabinet = 0).
4. **Optional cleanups**: choose power/network/project/tag/custom/VM/log/rack cleanups to run before disposal.
5. **Actions**: ignore/create minimal entries, move to storage, confirm disposal, etc.
6. **Finalize**: choose disposition method/date, run `Device::Dispose()` for confirmed devices and log each action.
7. **Log & Rollback**: download CSV log; rollback restores cabinet/position and removes fac_DispositionMembership updates.
8. **Reset**: reloading the page resets the wizard for a new file.

## Étapes du workflow (FR)
1. **Importer** le fichier Excel ; les en-têtes sont détectées jusqu’à la ligne 20.  
2. **Sélectionner** les onglets à traiter et confirmer/ajuster la colonne de numéro de série détectée (SN, Serial Number, Numéro de série, etc.).  
3. **Catégoriser** les équipements :
   - Catégorie 1 : numéros inconnus (absents de la base).
   - Catégorie 2 : équipements encore installés (Cabinet > 0).
   - Catégorie 3 : équipements présents en Storage Room (Cabinet = -1).
   - Catégorie 4 : équipements déjà sortis (Cabinet = 0).
4. **Nettoyages optionnels** : choisir les actions (énergie/réseau/projets/tags/attributs/VM/journaux/historique rack) à exécuter avant la destruction.
5. **Actions** : ignorer/créer des entrées minimales, déplacer en Storage Room, confirmer la méthode de sortie, etc.
6. **Traitement final** : choisir méthode/date, lancer `Device::Dispose()` pour chaque équipement confirmé avec journalisation.
7. **Journal & rollback** : export CSV, bouton d’annulation restaurent armoires/positions et nettoient fac_DispositionMembership.
8. **Réinitialisation** : actualiser la page (GET) redémarre l’assistant pour un nouveau fichier.

---

## Best Practices (EN)
- Validate serial coverage before import; use minimal entries only when required.
- Run a full DB backup before starting (warning displayed in UI).
- Download the log after each session to keep an auditable record.

## Bonnes pratiques (FR)
- Vérifier la présence des numéros avant import pour éviter les créations minimales.
- Faire une sauvegarde complète avant le traitement automatisé (avertissement sur la page).
- Télécharger le journal après chaque session pour conserver une trace exploitable.
