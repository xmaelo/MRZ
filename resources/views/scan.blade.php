<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="csrf-token" content="{{ csrf_token() }}"> <!-- Ajouter le meta tag CSRF -->
    <title>Scan & MRZ</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <!-- Formulaire d'importation de fichier -->
    <h2>Fichier importé</h2>
@if($filePath)
    <p>Le fichier a été importé avec succès ! Chemin du fichier : {{ $filePath }}</p>
@else
    <p>Aucun fichier importé.</p>
@endif

    <h2>Importer un fichier</h2>
    <form id="importForm" enctype="multipart/form-data">
        <input type="file" name="file" id="fileInput">
        <button type="submit">Importer</button>
    </form>
    <div id="importResponse"></div>

    <!-- Bouton pour démarrer le scan -->
    <h2>Commencer le scan</h2>
    <button id="scanButton">Démarrer le Scan</button>
    <div id="scanResponse"></div>

    <!-- Bouton pour extraire la MRZ -->
    <h2>Extraire la MRZ</h2>
    <button id="extractMRZButton">Extraire MRZ</button>
    <div id="mrzResponse"></div>

    <script>
        $(document).ready(function() {
            // Ajouter le jeton CSRF dans les en-têtes des requêtes AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Importation du fichier
            $('#importForm').on('submit', function(event) {
                event.preventDefault();

                var formData = new FormData();
                formData.append('file', $('#fileInput')[0].files[0]);

                $.ajax({
                    url: '{{ route("scan.import") }}',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#importResponse').html('<p>' + response.success + '</p>');
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON;
                        $('#importResponse').html('<p>Error: ' + response.error + '</p>');
                    }
                });
            });

            // Démarrer le scan
            $('#scanButton').on('click', function() {
                $.ajax({
                    url: '{{ route("scan.perform") }}',
                    type: 'POST',
                    success: function(response) {
                        $('#scanResponse').html('<p>' + response.success + '</p>');
                        $('#scanResponse').append('<img src="' + response.imagePath + '" />');
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON;
                        $('#scanResponse').html('<p>Error: ' + response.error + '</p>');
                    }
                });
            });

            // Extraire la MRZ
            $('#extractMRZButton').on('click', function() {
                $.ajax({
                    url: '{{ route("scan.extractMRZscan") }}',
                    type: 'POST',
                    success: function(response) {
                        $('#mrzResponse').html('<p>' + response.success + '</p>');
                        $('#mrzResponse').append('<pre>' + JSON.stringify(response.mrzData, null, 2) + '</pre>');
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON;
                        $('#mrzResponse').html('<p>Error: ' + response.error + '</p>');
                    }
                });
            });
        });
    </script>

</body>
</html>
