<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>AI Chat Interface</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Load theme BEFORE rendering -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'flatly';
        document.write(
            `<link id="theme-link" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/${savedTheme}/bootstrap.min.css">`
        );
    </script>

    <style>
        body {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
        }

        .chat-container {
            height: 75vh;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            background-color: var(--bs-light);
        }

        .message-row {
            display: flex;
            flex-direction: column;
        }

        .user-msg,
        .ai-msg {
            max-width: 75%;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 20px;
            word-wrap: break-word;
        }

        .user-msg {
            background-color: var(--bs-success-bg-subtle);
            align-self: flex-end;
            color: black;
        }

        .ai-msg {
            background-color: var(--bs-danger-bg-subtle);
            align-self: flex-start;
            color:black;
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 15px 0;
        }

        #chat-box {
            display: flex;
            flex-direction: column;
        }

        .file-upload {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="text-end mb-2">
            <select id="themeSelector" class="form-select w-auto d-inline" onchange="switchTheme(this.value)">
                <option value="flatly">🌞 Light</option>
                <option value="darkly">🌙 Dark</option>
            </select>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header text-center bg-primary text-white">
                        <h4 class="mb-0">🧠 Smart AI Chat</h4>
                    </div>
                    <div class="card-body chat-container" id="chat-box"></div>
                    <div class="card-footer">
                        <div class="file-upload mb-2">
                            <img id="imagePreview" src="" class="d-none" style="max-width: 70px; margin-top: 10px;">

                            <input type="file" id="imageInput" accept="image/*" class="form-control">
                        </div>
                        <div class="input-group">

                            <textarea id="message" class="form-control" rows="1" placeholder="Type your message..."></textarea>
                            <button class="btn btn-primary" onclick="sendMessage()">Send</button>
                        </div>
                        <div id="loading" class="loading-spinner d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Thinking...</span>
                            </div>
                            <span class="ms-2 text-muted">AI is thinking...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const chatBox = document.getElementById('chat-box');
        const messageInput = document.getElementById('message');
        const loading = document.getElementById('loading');

        const colors = ["#e6f7ff", "#fff1b8", "#ffd6e7", "#d9f7be", "#f9f0ff", "#ffccc7", "#f6ffed", "#f4ffb8"];

        function getRandomColor() {
            return colors[Math.floor(Math.random() * colors.length)];
        }

        function appendMessage(content, sender = 'user') {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add(sender === 'user' ? 'user-msg' : 'ai-msg', 'message-row');
            messageDiv.style.backgroundColor = getRandomColor();

            if (typeof content === 'string') {
                // Check if content is a base64 image URL or normal text
                if (content.startsWith("data:image") || content.startsWith("http")) {
                    const img = document.createElement("img");
                    img.src = content;
                    img.style.maxWidth = "200px";
                    img.style.borderRadius = "10px";
                    messageDiv.appendChild(img);
                } else {
                    messageDiv.innerText = content;
                }
            } else if (content instanceof File) {
                const img = document.createElement("img");
                img.src = URL.createObjectURL(content);
                img.style.maxWidth = "200px";
                img.style.borderRadius = "10px";
                messageDiv.appendChild(img);
            }

            chatBox.appendChild(messageDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
        }


        function sendMessage() {
            const message = messageInput.value.trim();
            const imageInput = document.getElementById("imageInput");
            const imageFile = imageInput.files[0];


             if (!message && !imageFile) {
        appendMessage('⚠️ Please enter a message or select an image.', 'ai');
        return;
    }

    // Show preview
    if (message) appendMessage(message, 'user');
    if (imageFile) appendMessage(imageFile, 'user');

    // Clear inputs
    messageInput.value = '';
    imageInput.value = '';
    loading.classList.remove('d-none');

    const formData = new FormData();
    formData.append("message", message);
    if (imageFile) {
        formData.append("image", imageFile);
    }

    fetch("{{ route('chat.send') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        appendMessage(data.reply || '🤖 No response from AI.', 'ai');
    })
    .catch(err => {
        appendMessage('⚠️ Server Error: ' + err.message, 'ai');
    })
    .finally(() => {
        loading.classList.add('d-none');
    });
}


        messageInput.addEventListener("keypress", function(e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Theme Switch
        const themeLink = document.getElementById("theme-link");
        const themeSelector = document.getElementById("themeSelector");

        function switchTheme(theme) {
            themeLink.href = `https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/${theme}/bootstrap.min.css`;
            localStorage.setItem("theme", theme);
        }

        window.onload = () => {
            const savedTheme = localStorage.getItem("theme") || "flatly";
            themeSelector.value = savedTheme;
            switchTheme(savedTheme);
        };


        document.getElementById('imageInput').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('imagePreview');

    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
});

    </script>
</body>

</html>
