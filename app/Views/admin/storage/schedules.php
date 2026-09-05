<?php
/** @var array $user */
/** @var array $schedules */
/** @var array $providers */
$user = $user ?? [];
$schedules = $schedules ?? [];
$providers = $providers ?? [];
$pageTitle = 'Storage — Schedules';
?>
<style>
    .schedules-wrap { max-width: 960px; margin: 0 auto; padding: 32px 24px 48px; }
    .s-card { background: #232632; border: 1px solid #3A4257; border-radius: 16px; padding: 0; transition: border-color 0.2s, box-shadow 0.2s; overflow: hidden; }
    .s-card:hover { border-color: rgba(37,99,235,0.3); }
    .s-head { display: flex; align-items: center; gap: 14px; padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); }
    .s-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .s-body { padding: 24px; }
    .s-fields { display: flex; flex-direction: column; gap: 16px; }
    .s-field label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94A3B8; margin-bottom: 6px; }
    .s-input { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #3A4257; background: #181A20; color: #F1F5F9; font-size: 14px; font-family: inherit; outline: none; transition: all 0.2s; }
    .s-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
    .s-input::placeholder { color: #64748B; }
    .s-btn { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .s-btn-primary { background: #2563EB; color: #fff; }
    .s-btn-primary:hover { background: #3B82F6; box-shadow: 0 4px 12px rgba(37,99,235,0.3); transform: translateY(-1px); }
    .s-btn-danger { background: rgba(239,68,68,0.12); color: #F87171; border: 1px solid rgba(239,68,68,0.25); }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px 16px; border-bottom: 1px solid #3A4257; text-align: left; font-size: 13px; color: #F1F5F9; }
    th { color: #94A3B8; }
</style>

<div class="schedules-wrap">
    <div class="s-card settings-full">
        <div class="s-head">
            <div class="s-icon purple"><i class="fas fa-clock"></i></div>
            <div class="s-head-text">
                <h3>Schedules</h3>
                <p>Planification des backups automatiques</p>
            </div>
        </div>
        <div class="s-body">
            <div style="margin-bottom: 24px;">
                <label>Provider</label>
                <select class="s-input" id="scheduleProvider">
                    <option value="">-- Sélectionner un provider --</option>
                    <?php foreach ($providers as $p): ?>
                        <option value="<?= htmlspecialchars($p['id'] ?? '') ?>"><?= htmlspecialchars($p['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 24px;">
                <label>Tenant</label>
                <select class="s-input" id="scheduleTenant">
                    <option value="">-- Tous tenants --</option>
                </select>
            </div>
            <div style="margin-bottom: 24px;">
                <label>Name</label>
                <input type="text" class="s-input" id="scheduleName" placeholder="Auto Backup">
            </div>
            <div style="margin-bottom: 24px;">
                <label>Type</label>
                <select class="s-input" id="scheduleType">
                    <option value="full">Full</option>
                    <option value="incremental">Incremental</option>
                </select>
            </div>
            <div style="margin-bottom: 24px;">
                <label>Fréquence</label>
                <select class="s-input" id="scheduleFrequency">
                    <option value="daily">Horaire</option>
                    <option value="weekly">Hebdomadaire</option>
                    <option value="monthly">Mensuel</option>
                </select>
            </div>
            <div style="margin-bottom: 24px;">
                <label>Heure</label>
                <input type="time" class="s-input" id="scheduleTime" value="02:00:00">
            </div>
            <div style="margin-bottom: 24px;">
                <label>Jours</label>
                <select class="s-input" id="scheduleDays">
                    <option value="0">Dimanche</option>
                    <option value="1">Lundi</option>
                    <option value="2">Mardi</option>
                    <option value="3">Mercredi</option>
                    <option value="4">Jeudi</option>
                    <option value="5">Vendredi</option>
                    <option value="6">Samedi</option>
                </select>
            </div>
            <div style="margin-bottom: 24px;">
                <label>Rétention (jours)</label>
                <input type="number" class="s-input" id="scheduleRetention" value="30" min="1">
            </div>
            <button class="s-btn s-btn-primary" style="width: 100%;" onclick="createSchedule()">
                <i class="fas fa-clock"></i> Créer schedule
            </button>
        </div>
    </div>

    <div class="s-card settings-full" style="margin-top: 32px;">
        <div class="s-head">
            <div class="s-icon emerald"><i class="fas fa-history"></i></div>
            <div class="s-head-text">
                <h3>Schedules Existants</h3>
                <p>Plannings de backup programmés</p>
            </div>
        </div>
        <div class="s-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Provider</th>
                        <th>Tenant</th>
                        <th>Name</th>
                        <th>Fréquence</th>
                        <th>Heure</th>
                        <th>Rétention</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $i => $s): ?>
                        <tr style="animation: fadeUp <?= 0.1 + $i * 0.05 ?>s ease-out;">
                            <td><?= htmlspecialchars($s['id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($s['provider_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($s['tenant_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($s['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($s['frequency'] ?? '') ?></td>
                            <td><?= htmlspecialchars($s['time_of_day'] ?? '') ?></td>
                            <td><?= htmlspecialchars($s['retention_days'] ?? '') ?></td>
                            <td style="color: #10b981;">Actif</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top: 20px; color: #64748B; font-size: 13px;">Aucun schedule trouvé</p>
        </div>
    </div>
</div>