<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>בדיקת API תמיכות</title>
</head>
<body>
    <h1>בדיקת API תמיכות</h1>
    <button onclick="testAPI()">בדוק API</button>
    <pre id="result"></pre>

    <script>
        async function testAPI() {
            try {
                const response = await fetch('supports_api.php?action=get_all', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();
                document.getElementById('result').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('result').textContent = 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html>
