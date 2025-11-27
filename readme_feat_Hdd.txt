# HDD Workflow Automation Guide

## 1. Activation & Acc�s
- Assurez-vous que la fonctionnalit� HDD est activ�e (eature_hdd = enabled).
- Le rapport est disponible depuis **Reports > Asset Reports** si l�option est active.
- Les pages managementhdd.php et report_hdd.php n�cessitent l�autorisation ManageHDD dans droit utilisateur.
- Activé la fonctionnalité par modél d'équipement dans devicetemplate.

## 2. Vue Gestion HDD (managementhdd.php)
- Bouton **Certify Audit HDD** : enregistre une entr�e d�audit (visible dans View Log, Devices, Reports).
- Bouton **View HDD Activity Log** : affiche toutes les actions HDD_BULK_DESTROY, HDD_CSV_BATCH, HDD_Audit.
- Boutons **Destroy Selected** / **Export all to Excel** fonctionnent via le formulaire principal manageHddForm.

## 3. Rapport �HDD Management Report� (report_hdd.php)
### 3.1 Destruction classique
1. S�lectionner des HDD.
2. Cliquer sur **Add destruction proof** -> uploader un PDF/Excel/ODS et, si besoin, cocher Apply destroyed status... + date.
3. Soumettre : chaque HDD re�oit le fichier de preuve; si l�option est coch�e, statut/dates sont mis � jour.
4. Les actions sont journalis�es (HDD_BULK_DESTROY) avec heure/utilisateur.

### 3.2 Traitement CSV automatis�
1. Cliquer sur **Process CSV Batch**.
2. Choisir un fichier CSV (UTF-8, max 2 Mo).
3. Une fois le fichier charg�, s�lectionner la **colonne contenant les serial numbers**.
4. (Optionnel) Fournir un fichier de preuve commun + cocher l�application du statut/dates.
5. (Optionnel) Remplir le champ **Note/Reference** (appara�t dans le log).
6. Soumettre :
   - Les SN d�j� d�truits sont ignor�s et signal�s (message + log).
   - Les SN inconnus provoquent une demande de confirmation avant de continuer.
   - Seuls les SN valides re�oivent la preuve / statut.
   - Un fichier .txt est t�l�charg� automatiquement, r�capitulant la note, les SN trait�s/ignor�s et l�horodatage.
   - Une entr�e HDD_CSV_BATCH est ajout�e dans ac_GenericLog (trace utilisateur + JSON r�capitulatif).

## 4. Consultation des journaux (hdd_log_view.php)
- Accessible via le bouton **View HDD Activity Log** (ou directement hdd_log_view.php?DeviceID=...).
- Colonne **Action** : HDD_BULK_DESTROY, HDD_CSV_BATCH, HDD_Audit (ou futurs types).
- Colonne **Details** :
   - Bulk destroy : nombre de HDD et IDs.
   - CSV batch : note, liste des SN trait�s / d�j� trait�s / inconnus.
   - Audit : simple confirmation.

## 5. Messages & Traductions
- Tous les textes des modales/boutons/messages ont une traduction fran�aise dans locale/fr_FR/LC_MESSAGES/openDCIM.po (section �New strings for HDD CSV automation and logging�).

## 6. Points de vigilance / �volutions pr�vues
- Les CSV doivent contenir au moins une colonne SN ; chaque colonne est analys�e apr�s upload (d�limiteurs auto : , ; tab |).
- Les futures �volutions pr�vues incluent l�import natif d�OCS Inventory pour afficher l��tat des disques (On/Off), faciliter maintenance/destruction et int�grer un flux 100% automatis�.
