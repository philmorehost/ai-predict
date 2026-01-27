<?php
if (!file_exists('includes/config.php')) {
    header('Location: install/');
    exit;
}
require_once 'includes/header.php';

$header_ad = getAd($conn, 'header_top');
$mid_ad = getAd($conn, 'mid_content');
$footer_ad = getAd($conn, 'result_footer');

// Fetch last 10 predictions
$last_predictions = $conn->query("SELECT * FROM prediction_cache ORDER BY created_at DESC LIMIT 10");
?>

<div class="max-w-5xl mx-auto px-4 py-12 md:py-20">
    <!-- Ad Slot: Header Top -->
    <?php if ($header_ad): ?>
        <div class="mb-12 flex justify-center"><?php echo $header_ad; ?></div>
    <?php endif; ?>

    <!-- Header -->
    <header class="text-center mb-16 space-y-4">
        <h1 class="text-4xl md:text-6xl font-black tracking-tight text-slate-900 dark:text-white">
            <?php
                $name_parts = explode('.', $settings['site_name'] ?: 'surepredictor.com');
                echo $name_parts[0];
                if (isset($name_parts[1])) echo '<span class="text-emerald-600">.' . $name_parts[1] . '</span>';
            ?>
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-lg md:text-xl max-w-2xl mx-auto font-light">
            <?php echo $settings['site_description']; ?>
        </p>
    </header>

    <!-- Prediction Input Form -->
    <div id="main-prediction-box" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-8 rounded-3xl shadow-xl mb-12 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 blur-[100px] -mr-32 -mt-32 rounded-full"></div>
        <form id="prediction-form" class="relative z-10 space-y-8">
            <div class="flex flex-col md:flex-row gap-6 items-center">
                <!-- Home Team -->
                <div class="relative flex-1 w-full">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Home Team</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-house text-emerald-600"></i>
                        </div>
                        <input type="text" id="home-team" autocomplete="off" placeholder="e.g. Real Madrid" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                        <div id="home-suggestions" class="absolute z-50 w-full mt-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden hidden max-h-60 overflow-y-auto"></div>
                    </div>
                </div>

                <div class="hidden md:flex flex-col items-center justify-center mt-6">
                    <span class="text-slate-300 font-bold italic">VS</span>
                </div>

                <!-- Away Team -->
                <div class="relative flex-1 w-full">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Away Team</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-plane text-emerald-600"></i>
                        </div>
                        <input type="text" id="away-team" autocomplete="off" placeholder="e.g. Manchester City" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                        <div id="away-suggestions" class="absolute z-50 w-full mt-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden hidden max-h-60 overflow-y-auto"></div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center">
                <button type="submit" id="submit-btn" class="w-full md:w-auto px-12 py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-lg rounded-2xl transition-all shadow-lg shadow-emerald-600/20 active:scale-95 flex items-center justify-center gap-3">
                    <i class="fas fa-bolt"></i>
                    Get Prediction
                </button>
                <p class="mt-4 text-xs text-slate-400 italic">
                    *AI analysis based on current squad data and latest league standings.
                </p>
            </div>
        </form>
    </div>

    <!-- Ad Slot: Mid Content -->
    <?php if ($mid_ad): ?>
        <div class="mb-12 flex justify-center"><?php echo $mid_ad; ?></div>
    <?php endif; ?>

    <!-- Last 10 Match Forecasts -->
    <?php if ($settings['history_enabled']): ?>
    <div class="mb-16">
        <div class="flex items-center gap-4 mb-8">
            <div class="h-px bg-slate-100 dark:bg-slate-800 flex-1"></div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tighter italic">Last 10 Match Forecasts</h3>
            <div class="h-px bg-slate-100 dark:bg-slate-800 flex-1"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php while($pred = $last_predictions->fetch_assoc()): ?>
                <?php $p_data = json_decode($pred['result_json'], true); ?>
                <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl p-6 shadow-sm flex items-center justify-between group hover:border-emerald-200 transition-all cursor-pointer">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800 dark:text-white"><?php echo $pred['home_team']; ?></span>
                            <span class="text-[10px] text-slate-300 font-black italic">VS</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white"><?php echo $pred['away_team']; ?></span>
                        </div>
                        <div class="text-[10px] font-black text-emerald-600 uppercase tracking-widest"><?php echo $p_data['mainPrediction'] ?? 'Analyzed'; ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter"><?php echo $p_data['expectedScore'] ?? '?-?'; ?></div>
                        <div class="text-[9px] text-slate-400 font-bold"><?php echo date('M j', strtotime($pred['created_at'])); ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Results Section -->
    <div id="results-container" class="mt-12 min-h-[300px]">
        <div id="empty-state" class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-3xl">
            <i class="fas fa-chart-bar text-4xl text-slate-200 dark:text-slate-700 mb-4"></i>
            <p class="text-slate-400 dark:text-slate-500">Search for teams to generate professional AI analysis</p>
        </div>

        <div id="loading-state" class="hidden flex-col items-center justify-center space-y-6 py-20">
             <div class="relative h-16 w-16">
                <div class="absolute inset-0 border-4 border-emerald-100 dark:border-emerald-900 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
             </div>
             <p class="text-slate-500 dark:text-slate-400 animate-pulse text-center">
               Synchronizing latest team data and tactical performance stats...
             </p>
        </div>

        <div id="prediction-result" class="hidden">
            <!-- Result will be injected here -->
        </div>
    </div>

    <!-- Ad Slot: Result Footer -->
    <?php if ($footer_ad): ?>
        <div class="mt-12 flex justify-center"><?php echo $footer_ad; ?></div>
    <?php endif; ?>
</div>

<!-- Floating WhatsApp Icon -->
<?php if ($settings['whatsapp_number']): ?>
<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['whatsapp_number']); ?>?text=<?php echo urlencode($settings['whatsapp_text'] ?: 'Hello, I would like to inquire about advertisement placement.'); ?>" target="_blank" class="fixed bottom-8 right-8 z-[150] h-14 w-14 bg-[#25D366] text-white rounded-full shadow-2xl flex items-center justify-center text-2xl animate-bounce transition-all hover:scale-110">
    <i class="fab fa-whatsapp"></i>
    <div class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 rounded-full border-2 border-white animate-pulse"></div>
</a>
<?php endif; ?>

    const form = document.getElementById('prediction-form');
    const homeInput = document.getElementById('home-team');
    const awayInput = document.getElementById('away-team');
    const submitBtn = document.getElementById('submit-btn');
    const resultsContainer = document.getElementById('results-container');
    const emptyState = document.getElementById('empty-state');
    const loadingState = document.getElementById('loading-state');
    const predictionResult = document.getElementById('prediction-result');

    if (form) form.addEventListener('submit', (e) => {
        e.preventDefault();
        const home = homeInput.value;
        const away = awayInput.value;

        if (!home || !away) return;

        emptyState.classList.add('hidden');
        predictionResult.classList.add('hidden');
        loadingState.classList.remove('hidden');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="animate-spin h-5 w-5 border-3 border-white border-t-transparent rounded-full"></div> AI Analyzing Matchup...';

        const visitorParam = activeVisitor ? `&visitor_id=${activeVisitor.user_id}` : '';
        fetch(`api/get_prediction.php?home=${encodeURIComponent(home)}&away=${encodeURIComponent(away)}${visitorParam}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    if (data.error === 'limit_exceeded' || data.error === 'insufficient_credits') {
                        showSubscribeModal();
                    }
                    // Handle specific AI errors or generic ones
                    const errorMsg = data.message || data.error;
                    throw new Error(errorMsg);
                }

                // Update local balance if logged in
                if (activeVisitor) {
                    const charge = <?php echo $settings['prediction_charge']; ?>;
                    activeVisitor.credits -= charge;
                    updateUIForVisitor();
                }

                renderPrediction(data, home, away);
            })
            .catch(err => {
                predictionResult.innerHTML = `
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 text-red-600 dark:text-red-400 p-6 rounded-2xl flex items-center gap-4">
                        <i class="fas fa-circle-exclamation text-xl"></i>
                        <p>${err.message}</p>
                    </div>
                `;
                predictionResult.classList.remove('hidden');
            })
            .finally(() => {
                loadingState.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-bolt"></i> Get Prediction';
            });
    });

    // Autocomplete Logic
    const setupAutocomplete = (input, suggestionsList) => {
        let debounceTimer;
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const val = input.value;
            if (val.length < 2) {
                suggestionsList.innerHTML = '';
                suggestionsList.classList.add('hidden');
                return;
            }

            // Visual feedback while typing
            input.parentElement.classList.add('opacity-70');

            debounceTimer = setTimeout(() => {
                fetch(`api/get_teams.php?q=${encodeURIComponent(val)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            suggestionsList.innerHTML = data.map(item => `
                                <li class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer border-b border-slate-100 dark:border-slate-700 last:border-0 transition-colors" onclick="selectTeam('${input.id}', '${item.name.replace(/'/g, "\\'")}')">
                                    <div class="font-medium text-slate-900 dark:text-white">${item.name}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 flex justify-between">
                                        <span>${item.league}</span>
                                        <span class="italic">${item.country}</span>
                                    </div>
                                </li>
                            `).join('');
                            suggestionsList.classList.remove('hidden');
                        } else {
                            suggestionsList.classList.add('hidden');
                        }
                    })
                    .finally(() => {
                        input.parentElement.classList.remove('opacity-70');
                    });
            }, 150);
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !suggestionsList.contains(e.target)) {
                suggestionsList.classList.add('hidden');
            }
        });
    };

    window.selectTeam = (inputId, name) => {
        const input = document.getElementById(inputId);
        input.value = name;
        document.getElementById(inputId + '-suggestions').classList.add('hidden');
    };

    setupAutocomplete(homeInput, document.getElementById('home-suggestions'));
    setupAutocomplete(awayInput, document.getElementById('away-suggestions'));

    function renderPrediction(data, home, away) {
        const overUnderColor = data.overUnderPrediction.includes('Over') ? 'text-blue-600' : 'text-amber-600';
        const overUnderBg = data.overUnderPrediction.includes('Over') ? 'bg-blue-50 border-blue-100 text-blue-700' : 'bg-amber-50 border-amber-100 text-amber-700';

        predictionResult.innerHTML = `
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-3xl overflow-hidden shadow-2xl animate-in fade-in slide-in-from-bottom-6 duration-1000 ease-out">
                <div class="bg-gradient-to-br from-emerald-600 via-emerald-500 to-green-500 p-8 text-center relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                        <i class="fas fa-futbol text-9xl -ml-10 -mt-10 rotate-12 text-white"></i>
                    </div>
                    <h2 class="text-3xl font-black text-white mb-2 relative z-10 tracking-tight">Match Forecast</h2>
                    <div class="relative z-10 flex items-center justify-center gap-3 mt-4">
                        <span class="bg-white/20 backdrop-blur-md px-4 py-1.5 rounded-xl text-white font-bold text-lg border border-white/30">${home}</span>
                        <span class="text-white/70 font-black italic">VS</span>
                        <span class="bg-white/20 backdrop-blur-md px-4 py-1.5 rounded-xl text-white font-bold text-lg border border-white/30">${away}</span>
                    </div>
                </div>

                <div class="p-8 md:p-12 space-y-12">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <!-- Main Outcome -->
                        <div class="flex flex-col items-center text-center space-y-6">
                            <div class="space-y-1">
                                <span class="text-slate-400 text-xs font-bold uppercase tracking-widest">Confidence Level</span>
                                <h3 class="text-xl font-extrabold text-slate-800 dark:text-white tracking-tight">Main Outcome</h3>
                            </div>

                            <div class="h-48 w-48 relative">
                                <svg class="w-full h-full" viewBox="0 0 100 100">
                                    <circle class="text-slate-50 dark:text-slate-700 stroke-current" stroke-width="8" fill="transparent" r="40" cx="50" cy="50"/>
                                    <circle class="text-emerald-600 stroke-current" stroke-width="8" stroke-linecap="round" fill="transparent" r="40" cx="50" cy="50"
                                        stroke-dasharray="251.2" stroke-dashoffset="${251.2 - (251.2 * data.mainProbability) / 100}" transform="rotate(-90 50 50)"/>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-black text-emerald-600">${data.mainProbability}%</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase">Probability</span>
                                </div>
                            </div>

                            <div class="bg-emerald-50 dark:bg-emerald-900/20 px-6 py-3 rounded-2xl border border-emerald-100 dark:border-emerald-800">
                                <span class="text-2xl font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-tighter">${data.mainPrediction}</span>
                            </div>
                        </div>

                        <!-- Over/Under -->
                        <div class="flex flex-col items-center text-center space-y-6">
                            <div class="space-y-1">
                                <span class="text-slate-400 text-xs font-bold uppercase tracking-widest">Goals Analysis</span>
                                <h3 class="text-xl font-extrabold text-slate-800 dark:text-white tracking-tight">Over/Under 2.5</h3>
                            </div>

                            <div class="h-48 w-48 relative">
                                <svg class="w-full h-full" viewBox="0 0 100 100">
                                    <circle class="text-slate-50 dark:text-slate-700 stroke-current" stroke-width="8" fill="transparent" r="40" cx="50" cy="50"/>
                                    <circle class="${overUnderColor.replace('text', 'stroke')} stroke-current" stroke-width="8" stroke-linecap="round" fill="transparent" r="40" cx="50" cy="50"
                                        stroke-dasharray="251.2" stroke-dashoffset="${251.2 - (251.2 * data.overUnderProbability) / 100}" transform="rotate(-90 50 50)"/>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-black ${overUnderColor}">${data.overUnderProbability}%</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase">Confidence</span>
                                </div>
                            </div>

                            <div class="px-6 py-3 rounded-2xl border ${overUnderBg} dark:bg-opacity-20">
                                <span class="text-2xl font-black uppercase tracking-tighter">${data.overUnderPrediction}</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-10">
                        <div class="flex flex-col items-center">
                            <div class="px-8 py-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-full flex items-center gap-4 shadow-sm">
                                <span class="text-slate-400 font-semibold uppercase text-xs tracking-widest">Expected Score</span>
                                <span class="text-slate-900 dark:text-white font-black text-3xl tracking-tighter">${data.expectedScore}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="bg-slate-50 dark:bg-slate-900/50 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800 relative">
                                <div class="absolute -top-4 left-6 bg-emerald-600 text-white p-2 rounded-xl shadow-lg">
                                    <i class="fas fa-brain"></i>
                                </div>
                                <h3 class="text-slate-800 dark:text-white font-black text-lg mb-4 mt-2 uppercase tracking-tight">AI Reasoning</h3>
                                <p class="text-slate-600 dark:text-slate-400 leading-relaxed font-medium">${data.reasoning}</p>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-900/50 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800 relative">
                                <div class="absolute -top-4 left-6 bg-slate-800 dark:bg-slate-700 text-white p-2 rounded-xl shadow-lg">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h3 class="text-slate-800 dark:text-white font-black text-lg mb-4 mt-2 uppercase tracking-tight">Key Insights</h3>
                                <ul class="space-y-4">
                                    ${data.keyStats.map((stat, idx) => `
                                        <li class="flex items-center text-slate-600 dark:text-slate-400 bg-white/50 dark:bg-slate-800/50 p-3 rounded-xl border border-white/80 dark:border-slate-700/80">
                                            <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 h-6 w-6 rounded-full flex items-center justify-center text-[10px] font-bold mr-3 shrink-0">${idx + 1}</span>
                                            <span class="font-medium text-sm">${stat}</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        predictionResult.classList.remove('hidden');
    }
</script>

<?php require_once 'includes/footer.php'; ?>
