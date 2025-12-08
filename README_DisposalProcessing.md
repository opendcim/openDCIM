# Disposal Processing Workflow

Ce document décrit la nouvelle page `DisposalProcessing.php`, disponible via le menu **Bulk Importer > Process Scrap**. Elle est réservée aux administrateurs de site et permet de traiter les équipements sortis en rebut sur la base d’un fichier Excel fourni par la logistique.

## Fichier attendu

- **Format** : Excel (XLSX/XLS) unique par opération.
- **Origine** : fichier préparé par l’équipe logistique listant les équipements planifiés pour destruction/recyclage.
- **Colonne pivot** : `Serial/No` (ou équivalent). Cette colonne est obligatoire car elle identifie de façon unique chaque équipement dans OpenDCIM. Les autres colonnes peuvent être présentes mais ne sont pas utilisées par l’outil.

> Conseil : vérifiez que tous les numéros de série sont bien renseignés dans OpenDCIM avant l’import, afin de limiter le nombre de créations minimales.

## Étapes du workflow

1. **Téléversement du fichier**  
   - Charger le fichier Excel via le formulaire.  
   - L’outil lit la première ligne pour détecter les entêtes.
2. **Sélection de la colonne Serial Number**  
   - Choisir la colonne qui contient les numéros de série (ex : `Serial/No`).  
   - Les numéros détectés sont dédupliqués avant l’analyse.
3. **Analyse & catégorisation**  
   Les équipements sont classés dans quatre catégories :
   - **Catégorie 1 – Unknown serial numbers** : numéros non trouvés en base.  
   - **Catégorie 2 – Devices not in the Storage Room** : SN trouvés mais toujours installés (Cabinet > 0).  
   - **Catégorie 3 – Devices ready in the Storage Room** : SN déjà en Storage Room (Cabinet = -1).  
   - **Catégorie 4 – Already processed** : SN déjà sortis (Cabinet = 0). Affichés en gris et ignorés pour la suite.
4. **Actions par catégorie**  
   - **Catégorie 1** : ignorer les SN inconnus ou créer automatiquement un équipement minimal (Label = SN, SerialNo = SN, Cabinet = -1).  
   - **Catégorie 2** : sélectionner les équipements repérés et déclencher le déplacement automatique vers la Storage Room.  
   - **Catégorie 3** : confirmer la liste et choisir la méthode de sortie (fac_Disposition) + date d’opération.  
   - **Catégorie 4** : consultatif, aucune action.
5. **Traitement final**  
   - Sélectionner la méthode de disposition et la date, puis cliquer sur **Start Processing**.  
   - L’outil appelle `Device::Dispose()` pour chaque équipement confirmé et enregistre les actions dans un journal.
6. **Journal & rollback**  
   - Le journal peut être téléchargé au format CSV.  
   - Après traitement, un bouton **Undo last operation** permet de restaurer les cabinets/positions et de supprimer les entrées fac_DispositionMembership ajoutées par l’opération.
7. **Réinitialisation**  
   - Le workflow conserve l’état tant que vous restez sur la page. Un simple rechargement (GET) réinitialise l’assistant pour un nouveau fichier.

## Bonnes pratiques

- Vérifier que tous les numéros de série du fichier logistique sont présents dans OpenDCIM avant import ; sinon, utiliser l’option de création minimale.  
- Effectuer une sauvegarde complète de la base avant de lancer le traitement automatisé (message d’avertissement affiché en haut de page).  
- Télécharger le journal après chaque session pour conserver une trace des opérations.

En cas de question ou d’évolution souhaitée, référez-vous à la page `DisposalProcessing.php` et aux traductions associées (`openDCIM.po`). 
