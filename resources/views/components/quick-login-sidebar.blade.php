<!-- Quick Login Sidebar - FOR TESTING ONLY -->
<div class="fixed left-0 top-0 h-full w-80 bg-gradient-to-b from-gray-900 to-gray-800 text-white shadow-2xl z-50 overflow-y-auto border-r border-gray-700" x-data="{ openGroups: [] }">
    <!-- Header -->
    <div class="sticky top-0 bg-gray-900/95 backdrop-blur-sm border-b border-yellow-500/30 p-4 z-10">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <h3 class="text-lg font-bold text-yellow-400">Testing Mode</h3>
            </div>
            <button 
                type="button"
                @click="openGroups = []"
                class="px-3 py-1.5 text-xs font-medium bg-gray-700 hover:bg-gray-600 rounded-md transition-colors border border-gray-600 flex items-center gap-1.5"
                title="Collapse All Groups">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
                Collapse All
            </button>
        </div>
        <p class="text-xs text-gray-400">Quick Login Feature</p>
    </div>

    <!-- User Groups -->
    <div class="p-4 space-y-2">
        <!-- Administrative Users -->
        <div class="border border-gray-700 rounded-lg overflow-hidden bg-gray-800/50">
            <button 
                type="button"
                @click="openGroups.includes('administrative') ? openGroups = openGroups.filter(g => g !== 'administrative') : openGroups.push('administrative')"
                class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-700/50 transition-colors">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <h4 class="text-sm font-semibold text-gray-300">Administrative</h4>
                </div>
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                     :class="{ 'rotate-180': openGroups.includes('administrative') }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="openGroups.includes('administrative')" 
                 x-collapse
                 class="px-4 pb-3 space-y-2">
                <button type="button"
                    onclick="quickLogin('sysadmin@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-red-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">System Admin</div>
                            <div class="text-xs text-red-100/80">sysadmin@cagsu.edu.ph</div>
                        </div>
                        <svg class="w-4 h-4 text-red-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('supply@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-blue-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">Supply Officer</div>
                            <div class="text-xs text-blue-100/80">Ronnie S. Agcaoili</div>
                        </div>
                        <svg class="w-4 h-4 text-blue-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('budget@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-500 hover:to-purple-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-purple-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">Budget Officer</div>
                            <div class="text-xs text-purple-100/80">Catalina B. Talosig</div>
                        </div>
                        <svg class="w-4 h-4 text-purple-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('executive@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-indigo-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">Executive Officer</div>
                            <div class="text-xs text-indigo-100/80">Rodel Francisco T. Alegado</div>
                        </div>
                        <svg class="w-4 h-4 text-indigo-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </div>

        <!-- BAC Users -->
        <div class="border border-gray-700 rounded-lg overflow-hidden bg-gray-800/50">
            <button 
                type="button"
                @click="openGroups.includes('bac') ? openGroups = openGroups.filter(g => g !== 'bac') : openGroups.push('bac')"
                class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-700/50 transition-colors">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h4 class="text-sm font-semibold text-gray-300">BAC Committee</h4>
                </div>
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                     :class="{ 'rotate-180': openGroups.includes('bac') }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="openGroups.includes('bac')" 
                 x-collapse
                 class="px-4 pb-3 space-y-2">
                <button type="button"
                    onclick="quickLogin('bac.chairman@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-500 hover:to-orange-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-orange-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">BAC Chairman</div>
                            <div class="text-xs text-orange-100/80">Christopher R. Garingan</div>
                        </div>
                        <svg class="w-4 h-4 text-orange-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('bac.vicechairman@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-400 hover:to-orange-500 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-orange-400/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">BAC Vice Chairman</div>
                            <div class="text-xs text-orange-100/80">Allan O. De La Cruz</div>
                        </div>
                        <svg class="w-4 h-4 text-orange-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('bac.member1@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-amber-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">BAC Member 1</div>
                            <div class="text-xs text-amber-100/80">Valentin M. Apostol</div>
                        </div>
                        <svg class="w-4 h-4 text-amber-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('bac.member2@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-amber-400/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">BAC Member 2</div>
                            <div class="text-xs text-amber-100/80">Chris Ian T. Rodriguez</div>
                        </div>
                        <svg class="w-4 h-4 text-amber-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('bac.member3@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-amber-300/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">BAC Member 3</div>
                            <div class="text-xs text-amber-100/80">Melvin S. Atayan</div>
                        </div>
                        <svg class="w-4 h-4 text-amber-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('bac.secretary@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-yellow-600 to-yellow-700 hover:from-yellow-500 hover:to-yellow-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-yellow-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">BAC Secretary</div>
                            <div class="text-xs text-yellow-100/80">Chanda T. Aquino</div>
                        </div>
                        <svg class="w-4 h-4 text-yellow-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </div>

        <!-- Financial Users -->
        <div class="border border-gray-700 rounded-lg overflow-hidden bg-gray-800/50">
            <button 
                type="button"
                @click="openGroups.includes('financial') ? openGroups = openGroups.filter(g => g !== 'financial') : openGroups.push('financial')"
                class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-700/50 transition-colors">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h4 class="text-sm font-semibold text-gray-300">Financial & Procurement</h4>
                </div>
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                     :class="{ 'rotate-180': openGroups.includes('financial') }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="openGroups.includes('financial')" 
                 x-collapse
                 class="px-4 pb-3 space-y-2">
                <button type="button"
                    onclick="quickLogin('accounting@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-500 hover:to-teal-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-teal-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">Accounting Officer</div>
                            <div class="text-xs text-teal-100/80">Fely Jane R. Reyes</div>
                        </div>
                        <svg class="w-4 h-4 text-teal-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('canvassing@cagsu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-500 hover:to-cyan-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-cyan-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">Canvassing Officer</div>
                            <div class="text-xs text-cyan-100/80">Chito D. Temporal</div>
                        </div>
                        <svg class="w-4 h-4 text-cyan-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </div>

        <!-- College Deans -->
        <div class="border border-gray-700 rounded-lg overflow-hidden bg-gray-800/50">
            <button 
                type="button"
                @click="openGroups.includes('colleges') ? openGroups = openGroups.filter(g => g !== 'colleges') : openGroups.push('colleges')"
                class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-700/50 transition-colors">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                    </svg>
                    <h4 class="text-sm font-semibold text-gray-300">College Deans</h4>
                </div>
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                     :class="{ 'rotate-180': openGroups.includes('colleges') }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="openGroups.includes('colleges')" 
                 x-collapse
                 class="px-4 pb-3 space-y-2">
                <button type="button"
                    onclick="quickLogin('calayanextension.sanchezmira@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-emerald-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">Calayan Extension</div>
                            <div class="text-xs text-emerald-100/80">Dullit, Rex S., MSA</div>
                        </div>
                        <svg class="w-4 h-4 text-emerald-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('maycmartinez03@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-500 hover:to-green-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-green-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">College of Agriculture</div>
                            <div class="text-xs text-green-100/80">Ms. May M. Leaño</div>
                        </div>
                        <svg class="w-4 h-4 text-green-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('cbea.sanchezmira@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-lime-600 to-lime-700 hover:from-lime-500 hover:to-lime-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-lime-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">CBEA</div>
                            <div class="text-xs text-lime-100/80">Rey D. Viloria, CPA</div>
                        </div>
                        <svg class="w-4 h-4 text-lime-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('ccje.csusm@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-500 hover:to-teal-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-teal-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">CCJE</div>
                            <div class="text-xs text-teal-100/80">Dr. Jose Sheriff O. Panelo</div>
                        </div>
                        <svg class="w-4 h-4 text-teal-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('coe.sanchezmira@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-500 hover:to-cyan-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-cyan-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">COE</div>
                            <div class="text-xs text-cyan-100/80">Engr. Marvin D. Adorio</div>
                        </div>
                        <svg class="w-4 h-4 text-cyan-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('angelabtuliao@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-sky-600 to-sky-700 hover:from-sky-500 hover:to-sky-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-sky-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">CHM</div>
                            <div class="text-xs text-sky-100/80">Ms. Angela B. Tuliao</div>
                        </div>
                        <svg class="w-4 h-4 text-sky-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('cit.sanchezmira@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-400 hover:to-blue-500 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-blue-400/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">CIT</div>
                            <div class="text-xs text-blue-100/80">Ms. Jane Gladys A. Monje</div>
                        </div>
                        <svg class="w-4 h-4 text-blue-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('cics_csusm@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-400 hover:to-indigo-500 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-indigo-400/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">CICS</div>
                            <div class="text-xs text-indigo-100/80">Dr. Manny S. Alipio</div>
                        </div>
                        <svg class="w-4 h-4 text-indigo-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('ctedcsusm@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-violet-600 to-violet-700 hover:from-violet-500 hover:to-violet-600 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-violet-500/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">CTED</div>
                            <div class="text-xs text-violet-100/80">Dr. Verlino D. Baddu</div>
                        </div>
                        <svg class="w-4 h-4 text-violet-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>

                <button type="button"
                    onclick="quickLogin('graduateschool.sanchezmira@csu.edu.ph', 'password123')"
                    class="group w-full text-left px-4 py-3 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-400 hover:to-purple-500 rounded-lg text-sm transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-purple-400/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">Graduate School</div>
                            <div class="text-xs text-purple-100/80">Dr. Melba B. Rosales</div>
                        </div>
                        <svg class="w-4 h-4 text-purple-200 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="sticky bottom-0 bg-gray-900/95 backdrop-blur-sm border-t border-gray-700 p-4 space-y-2">
        <button type="button"
            onclick="clearLogin()"
            class="w-full px-4 py-2.5 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center gap-2 border border-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Clear Fields
        </button>
        <div class="text-center text-xs text-gray-500">
            Password: <span class="font-mono text-gray-400">password123</span>
        </div>
    </div>
</div>

<script>
    function quickLogin(email, password) {
        // Fill in the email and password fields
        const emailField = document.getElementById('email');
        const passwordField = document.getElementById('password');
        
        if (emailField) emailField.value = email;
        if (passwordField) passwordField.value = password;

        // Optional: Focus on the login button
        const loginButton = document.querySelector('button[type="submit"], .primary-button');
        if (loginButton) loginButton.focus();

        // Show a subtle notification
        showNotification(`Credentials loaded for: ${email.split('@')[0]}`);
    }

    function clearLogin() {
        const emailField = document.getElementById('email');
        const passwordField = document.getElementById('password');
        
        if (emailField) {
            emailField.value = '';
            emailField.focus();
        }
        if (passwordField) passwordField.value = '';
    }

    function showNotification(message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-3 rounded-lg shadow-lg border border-gray-700 z-50 animate-fade-in';
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm">${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Remove after 2 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(10px)';
            notification.style.transition = 'all 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }
</script>

<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fade-in {
        animation: fade-in 0.3s ease;
    }
</style>
