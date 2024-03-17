# BRA_script
Ce script récupère et met en cache les Bulletins des Risques d'Avalanche dpubliés par Météo France, afin de faciliter un accès rapide aux bulletins, et de pouvoir les envoyer par email à un (ou des) destinataire(s) choisi(s).

bra_public.php est un script de listing simple, qui parse la liste des bulletins publiés la veille et le jour même, et affiche la liste des liens directs pour télécharger les PDF correpondants.

script_bra.php fonctionne de manière similaire, mais est conçu pour être déclenché via une taĉhe cron quotidienne afin d'envoyer par email les PDF des bulletins sélectionnés. L'activation de l'envoi d'email est protégé par une clef secrète.


