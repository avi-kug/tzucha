<!-- Full Person Details Modal -->
<div class="modal fade" id="personDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-circle"></i> 
                    פרטים מלאים - <span id="personName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="personDetailsAccordion">
                    
                    <!-- Basic Info -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basicInfo">
                                <i class="bi bi-person-badge me-2"></i> פרטים כלליים
                            </button>
                        </h2>
                        <div id="basicInfo" class="accordion-collapse collapse show" data-bs-parent="#personDetailsAccordion">
                            <div class="accordion-body">
                                <div class="row" id="basicInfoContent"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cash Donations -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cashDonations">
                                <i class="bi bi-cash-coin me-2"></i> תרומות מזומן
                            </button>
                        </h2>
                        <div id="cashDonations" class="accordion-collapse collapse" data-bs-parent="#personDetailsAccordion">
                            <div class="accordion-body">
                                <div id="cashDonationsContent"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Standing Orders -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#standingOrders">
                                <i class="bi bi-arrow-repeat me-2"></i> הוראות קבע
                            </button>
                        </h2>
                        <div id="standingOrders" class="accordion-collapse collapse" data-bs-parent="#personDetailsAccordion">
                            <div class="accordion-body">
                                <div id="standingOrdersContent"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Supports -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#supports">
                                <i class="bi bi-gift me-2"></i> תמיכות שאושרו
                            </button>
                        </h2>
                        <div id="supports" class="accordion-collapse collapse" data-bs-parent="#personDetailsAccordion">
                            <div class="accordion-body">
                                <div id="supportsContent"></div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="editPersonFromDetailsBtn">
                    <i class="bi bi-pencil"></i> ערוך פרטים
                </button>
                <button type="button" class="btn btn-success d-none" id="savePersonFromDetailsBtn">
                    <i class="bi bi-check-lg"></i> שמור שינויים
                </button>
                <button type="button" class="btn btn-warning d-none" id="cancelEditFromDetailsBtn">
                    <i class="bi bi-x-lg"></i> ביטול
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">סגור</button>
            </div>
        </div>
    </div>
</div>
