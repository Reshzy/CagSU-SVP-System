<!-- Quick Login Sidebar - FOR TESTING ONLY -->
<div class="fixed left-0 top-0 h-full w-64 bg-gray-800 text-white p-4 shadow-lg z-50 overflow-y-auto">
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-yellow-400 mb-2">⚠️ TESTING ONLY</h3>
        <p class="text-xs text-gray-300 mb-4">Quick Login Feature</p>
    </div>

    <div class="space-y-2">
        <h4 class="text-sm font-medium text-gray-300 mb-3">Login as:</h4>

        <!-- System Admin -->
        <!-- <button type="button"
            onclick="quickLogin('admin@cagsu.edu.ph', 'admin123')"
            class="w-full text-left px-3 py-2 bg-red-600 hover:bg-red-700 rounded text-sm transition-colors">
            System Admin
        </button> -->

        <!-- Supply Officer -->
        <button type="button"
            onclick="quickLogin('supply@cagsu.edu.ph', 'supply123')"
            class="w-full text-left px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm transition-colors">
            Supply Officer
        </button>

        <!-- End User -->
        <button type="button"
            onclick="quickLogin('enduser@test.com', 'password')"
            class="w-full text-left px-3 py-2 bg-green-600 hover:bg-green-700 rounded text-sm transition-colors">
            End User
        </button>

        <!-- Budget Office -->
        <button type="button"
            onclick="quickLogin('budget@test.com', 'password')"
            class="w-full text-left px-3 py-2 bg-purple-600 hover:bg-purple-700 rounded text-sm transition-colors">
            Budget Office
        </button>

        <!-- Executive Officer -->
        <button type="button"
            onclick="quickLogin('executive@test.com', 'password')"
            class="w-full text-left px-3 py-2 bg-indigo-600 hover:bg-indigo-700 rounded text-sm transition-colors">
            Executive Officer
        </button>

        <!-- BAC Chair -->
        <button type="button"
            onclick="quickLogin('bacchair@test.com', 'password')"
            class="w-full text-left px-3 py-2 bg-orange-600 hover:bg-orange-700 rounded text-sm transition-colors">
            BAC Chair
        </button>

        <!-- BAC Member -->
        <button type="button"
            onclick="quickLogin('bacmember@test.com', 'password')"
            class="w-full text-left px-3 py-2 bg-orange-500 hover:bg-orange-600 rounded text-sm transition-colors">
            BAC Member
        </button>

        <!-- BAC Secretariat -->
        <button type="button"
            onclick="quickLogin('bacsec@test.com', 'password')"
            class="w-full text-left px-3 py-2 bg-orange-400 hover:bg-orange-500 rounded text-sm transition-colors">
            BAC Secretariat
        </button>

        <!-- Accounting Office -->
        <button type="button"
            onclick="quickLogin('accounting@test.com', 'password')"
            class="w-full text-left px-3 py-2 bg-teal-600 hover:bg-teal-700 rounded text-sm transition-colors">
            Accounting Office
        </button>

        <!-- Canvassing Unit -->
        <button type="button"
            onclick="quickLogin('canvassing@test.com', 'password')"
            class="w-full text-left px-3 py-2 bg-cyan-600 hover:bg-cyan-700 rounded text-sm transition-colors">
            Canvassing Unit
        </button>
    </div>

    <!-- Clear Button -->
    <button type="button"
        onclick="clearLogin()"
        class="w-full mt-4 px-3 py-2 bg-gray-600 hover:bg-gray-700 rounded text-sm transition-colors">
        Clear Fields
    </button>
</div>

<script>
    function quickLogin(email, password) {
        // Fill in the email and password fields
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;

        // Optional: Focus on the login button
        document.querySelector('button[type="submit"], .primary-button').focus();
    }

    function clearLogin() {
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
        document.getElementById('email').focus();
    }
</script>