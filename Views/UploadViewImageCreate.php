<?php
$uploadInstanceId = uniqid('upload-create-', false);
$fileInputId = $uploadInstanceId . '-file';
$previewId = $uploadInstanceId . '-preview';
$detailsId = $uploadInstanceId . '-details';
?>
<div class="app-upload">
    <input
        class="app-upload__input"
        id="<?php echo htmlspecialchars($fileInputId, ENT_QUOTES, 'UTF-8'); ?>"
        type="file"
        name="userfile"
        accept="image/*"
    >
    <output class="app-upload__preview" id="<?php echo htmlspecialchars($previewId, ENT_QUOTES, 'UTF-8'); ?>"></output>
    <output class="app-upload__details" id="<?php echo htmlspecialchars($detailsId, ENT_QUOTES, 'UTF-8'); ?>"></output>
</div>

<script>
    (function () {
        var fileInput = document.getElementById('<?php echo addslashes($fileInputId); ?>');
        var preview = document.getElementById('<?php echo addslashes($previewId); ?>');
        var details = document.getElementById('<?php echo addslashes($detailsId); ?>');

        if (!fileInput || !preview || !details) {
            return;
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function (character) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[character];
            });
        }

        function clearOutputs() {
            preview.innerHTML = '';
            details.innerHTML = '';
        }

        function appendPreview(file, result) {
            var span = document.createElement('span');
            span.innerHTML = '<img class="thumb app-upload__thumb" src="' + result + '" title="' + escapeHtml(file.name) + '">';
            preview.appendChild(span);
        }

        function renderFiles(fileList) {
            var files = Array.prototype.slice.call(fileList || []);
            var meta = [];

            clearOutputs();

            files.forEach(function (file) {
                if (!file.type || file.type.indexOf('image/') !== 0) {
                    return;
                }

                meta.push(
                    'Nombre: ' + escapeHtml(file.name) +
                    ' | Tamanio: ' + escapeHtml(file.size) +
                    ' bytes | Tipo: ' + escapeHtml(file.type)
                );

                var reader = new FileReader();
                reader.onload = function (event) {
                    appendPreview(file, event.target.result);
                };
                reader.readAsDataURL(file);
            });

            if (meta.length > 0) {
                details.innerHTML = '<ul><li>' + meta.join('</li><li>') + '</li></ul>';
            }
        }

        fileInput.addEventListener('change', function (event) {
            renderFiles(event.target.files);
        }, false);
    })();
</script>
