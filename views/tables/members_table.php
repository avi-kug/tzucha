<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 70vh;">
            <table id="mainTable" class="table table-hover table-striped table-bordered mb-0 align-middle">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th class="text-center"><input type="checkbox" id="selectAll"></th>
                        <th>פעולות</th>
                        <th>מס תורם</th>
                        <th>שם</th>
                        <th>משפחה</th>
                        <th>שם ומשפחה</th>
                        <th>ת.ז בעל</th>
                        <th>ת.ז אשה</th>
                        <th>שם האשה</th>
                        <th>כתובת</th>
                        <th>שכונה</th>
                        <th>קומה</th>
                        <th>עיר</th>
                        <th>טלפון</th>
                        <th>נייד בעל</th>
                        <th>נייד אשה</th>
                        <th>מייל</th>
                        <th class="text-center">אלפון</th>
                        <th class="text-center">אמרכל</th>
                        <th class="text-center">גזבר</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): foreach ($data as $row): ?>
                    <tr>
                        <td class="text-center"><input type="checkbox" value="<?= $row['id'] ?>"></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-edit-member-id="<?= (int)$row['id'] ?>">ערוך</button>
                        </td>
                        <td><?= htmlspecialchars($row['donor_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['first_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['last_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($row['id_husband'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['id_wife'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['wife_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['address'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['neighborhood'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['floor'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['city'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['mobile_husband'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['mobile_wife'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['email_updated'] ?? '') ?></td>
                        <td class="text-center"><?= ($row['is_phonebook'] ?? 0) ? '✅' : '' ?></td>
                        <td class="text-center"><?= ($row['is_admin'] ?? 0) ? '✅' : '' ?></td>
                        <td class="text-center"><?= ($row['is_treasurer'] ?? 0) ? '✅' : '' ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="20" class="text-center py-4">לא נמצאו נתונים להצגה</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>