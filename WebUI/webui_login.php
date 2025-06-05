<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - LBot管理后台</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f5f7fa;
            font-family: 'Noto Sans SC', sans-serif
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 15px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }

        .card-title {
            margin: 0;
            font-weight: 600;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
            border-color: #4361ee;
        }

        .btn-primary {
            background: #4361ee;
            border: none;
            padding: 10px 0;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #3f37c9;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title text-center">LBot管理后台</h3>
            </div>
            <div class="card-body p-4">
                <?php if ($login_attempted_with_error): ?>
                    <div class="alert alert-danger">访问密码错误，请重试</div>
                <?php endif; ?>
                <form id="login-form"> <!-- 移除 method 和 action, 由 JS 处理 -->
                    <div class="mb-3">
                        <label for="password_input" class="form-label">访问密码</label>
                        <input type="password" class="form-control" id="password_input" name="password" required
                            autofocus>
                        <div class="invalid-feedback" style="display: none;">请输入密码。</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">登录</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('login-form').addEventListener('submit', async function (e) { // 添加 async
            e.preventDefault(); // 阻止表单默认提交
            const passwordInput = document.getElementById('password_input');
            const plainPassword = passwordInput.value;
            const submitBtn = this.querySelector('button[type="submit"]');
            const invalidFeedback = this.querySelector('.invalid-feedback');
            if (submitBtn.disabled) {
                return;
            }
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 登录中...';
            passwordInput.classList.remove('is-invalid');
            invalidFeedback.style.display = 'none';

            if (!plainPassword) {
                passwordInput.classList.add('is-invalid');
                invalidFeedback.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '登录';
                return;
            }

            // 使用 SubtleCrypto API 进行 SHA-256 哈希
            async function calculateSHA256(message) {
                const msgBuffer = new TextEncoder().encode(message); // 将字符串编码为 UTF-8
                const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer); // 哈希处理
                const hashArray = Array.from(new Uint8Array(hashBuffer)); // 转换为字节数组
                const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join(''); // 转换为十六进制字符串
                return hashHex;
            }

            const hashedPassword = await calculateSHA256(plainPassword);

            // 构建新的 URL 并跳转
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('password', hashedPassword); // 使用 'password' 作为参数名传递哈希值
            window.location.href = currentUrl.toString();
        });
    </script>
</body>

</html>