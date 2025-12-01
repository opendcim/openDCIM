# HDD Workflow Automation Guide

## 1. Activation & Accï¿½s
- Assurez-vous que la fonctionnalitï¿½ HDD est activï¿½e (eature_hdd = enabled).
- Le rapport est disponible depuis **Reports > Asset Reports** si lï¿½option est active.
- Les pages managementhdd.php et report_hdd.php nï¿½cessitent lï¿½autorisation ManageHDD dans droit utilisateur.
- ActivÃ© la fonctionnalitÃ© par modÃ©l d'Ã©quipement dans devicetemplate.

## 2. Vue Gestion HDD (managementhdd.php)
- Bouton **Certify Audit HDD** : enregistre une entrï¿½e dï¿½audit (visible dans View Log, Devices, Reports).
- Bouton **View HDD Activity Log** : affiche toutes les actions HDD_BULK_DESTROY, HDD_CSV_BATCH, HDD_Audit.
- Boutons **Destroy Selected** / **Export all to Excel** fonctionnent via le formulaire principal manageHddForm.

## 3. Rapport ï¿½HDD Management Reportï¿½ (report_hdd.php)
### 3.1 Destruction classique
1. Sï¿½lectionner des HDD.
2. Cliquer sur **Add destruction proof** -> uploader un PDF/Excel/ODS et, si besoin, cocher Apply destroyed status... + date.
3. Soumettre : chaque HDD reï¿½oit le fichier de preuve; si lï¿½option est cochï¿½e, statut/dates sont mis ï¿½ jour.
4. Les actions sont journalisï¿½es (HDD_BULK_DESTROY) avec heure/utilisateur.

### 3.2 Traitement CSV automatisï¿½
1. Cliquer sur **Process CSV Batch**.
2. Choisir un fichier CSV (UTF-8, max 2 Mo).
3. Une fois le fichier chargï¿½, sï¿½lectionner la **colonne contenant les serial numbers**.
4. (Optionnel) Fournir un fichier de preuve commun + cocher lï¿½application du statut/dates.
5. (Optionnel) Remplir le champ **Note/Reference** (apparaï¿½t dans le log).
6. Soumettre :
   - Les SN dï¿½jï¿½ dï¿½truits sont ignorï¿½s et signalï¿½s (message + log).
   - Les SN inconnus provoquent une demande de confirmation avant de continuer.
   - Seuls les SN valides reï¿½oivent la preuve / statut.
   - Un fichier .txt est tï¿½lï¿½chargï¿½ automatiquement, rï¿½capitulant la note, les SN traitï¿½s/ignorï¿½s et lï¿½horodatage.
   - Une entrï¿½e HDD_CSV_BATCH est ajoutï¿½e dans ac_GenericLog (trace utilisateur + JSON rï¿½capitulatif).

## 4. Consultation des journaux (hdd_log_view.php)
- Accessible via le bouton **View HDD Activity Log** (ou directement hdd_log_view.php?DeviceID=...).
- Colonne **Action** : HDD_BULK_DESTROY, HDD_CSV_BATCH, HDD_Audit (ou futurs types).
- Colonne **Details** :
   - Bulk destroy : nombre de HDD et IDs.
   - CSV batch : note, liste des SN traitï¿½s / dï¿½jï¿½ traitï¿½s / inconnus.
   - Audit : simple confirmation.

## 5. Messages & Traductions
- Tous les textes des modales/boutons/messages ont une traduction franï¿½aise dans locale/fr_FR/LC_MESSAGES/openDCIM.po (section ï¿½New strings for HDD CSV automation and loggingï¿½).

## 6. Points de vigilance / ï¿½volutions prï¿½vues
- Les CSV doivent contenir au moins une colonne SN ; chaque colonne est analysï¿½e aprï¿½s upload (dï¿½limiteurs auto : , ; tab |).
- Les futures ï¿½volutions prï¿½vues incluent lï¿½import natif dï¿½OCS Inventory pour afficher lï¿½ï¿½tat des disques (On/Off), faciliter maintenance/destruction et intï¿½grer un flux 100% automatisï¿½.
## 7. API REST HDD
Pour préparer l’automatisation (OCS ou autres), quatre routes REST ont été ajoutées. Toutes nécessitent le droit `ManageHDD` (ou `SiteAdmin`) et les en-têtes d’authentification habituels.

### 7.1 GET /api/v1/hdd
- Paramètres optionnels : `DeviceID`, `HDDID` (valeur ou liste séparée par virgules), `Status` (On, Off, Pending_destruction, Destroyed, Spare), `SerialNo` (recherche partielle).
- Retour : tableau de modèles `HDD`.

### 7.2 GET /api/v1/hdd/{HDDID}
- Retour : détail du disque ciblé.

### 7.3 GET /api/v1/hdd/{HDDID}/proof
- Retour : JSON avec `ProofFile`, URL publique et chemin disque si le fichier existe. Aucun upload via API (GET uniquement).

### 7.4 PUT /api/v1/hdd
- Crée un disque sur un équipement. Champs requis : `DeviceID`, `SerialNo`. Champs optionnels : `Status`, `TypeMedia`, `Size`.
- Contrôle automatique du nombre de slots (message « slot hdd is full » si le device est plein).

### 7.5 POST /api/v1/hdd/{HDDID}
- Met à jour SerialNo/Status/TypeMedia/Size ou réaffecte le disque à un autre DeviceID (slots vérifiés).

### 7.6 DeviceTemplate & People
- `DeviceTemplate` expose désormais `EnableHDDFeature` et `HDDCount` via GET/POST/PUT.
- `People` expose le booléen `ManageHDD` pour activer l’accès API.

La description complète (modèles, exemples) est disponible dans `api/docs/swagger.yaml`, section `HDD`.
