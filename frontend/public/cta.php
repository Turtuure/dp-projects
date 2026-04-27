<?php
$u = $_SESSION['user'] ?? null;
$memberRoles = ['member', 'supporter', 'administrator', 'system_administrator', 'global_system_administrator'];
$isMember = $u !== null && in_array(effectiveRole(), $memberRoles, true);
?>
<section class="projects-cta text-center">
    <div class="container">
        <h2>Have an idea?</h2>
        <p>Propose a project and build it with the community.</p>

        <?php if ($isMember): ?>
        <button type="button" class="btn btn-dark btn-lg px-5 text-uppercase" data-bs-toggle="modal" data-bs-target="#propose-modal">
            Propose a project
        </button>
        <?php elseif ($u === null): ?>
        <a href="/?signin=1" class="btn btn-dark btn-lg px-5 text-uppercase">Propose a project</a>
        <?php else: ?>
        <a href="/join" class="btn btn-dark btn-lg px-5 text-uppercase">Become a member to propose</a>
        <?php endif; ?>
    </div>
</section>

<?php if ($isMember): ?>
<!-- Propose a Project modal -->
<div class="modal fade" id="propose-modal" tabindex="-1" aria-labelledby="propose-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="propose-modal-label">Propose a Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-2">
                <p class="text-muted mb-4" style="font-size:.92rem;">
                    Describe your idea and we'll review it. Good proposals get turned into real projects.
                </p>

                <div class="project-form-error d-none" id="propose-error" role="alert"></div>

                <form id="propose-form" autocomplete="off">
                    <input type="hidden" name="source_locale" value="<?= htmlspecialchars(I18n::locale(), ENT_QUOTES) ?>">

                    <div class="mb-3">
                        <label for="prop-title" class="form-label">Project title <span class="text-danger">*</span></label>
                        <input type="text" id="prop-title" name="title" class="form-control" required placeholder="What would you call this project?" />
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="prop-category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select id="prop-category" name="category" class="form-select" required>
                                <option value="">Select…</option>
                                <option value="community">Community</option>
                                <option value="technology">Technology</option>
                                <option value="events">Events</option>
                                <option value="research">Research</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="prop-summary" class="form-label">One-line summary <span class="text-danger">*</span></label>
                            <input type="text" id="prop-summary" name="summary" class="form-control" required placeholder="A sentence describing the idea" maxlength="300" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="prop-description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="prop-description" name="description" class="form-control" rows="5" required
                            placeholder="What problem does this solve? What would it look like in practice?"></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark px-5" id="propose-submit-btn">
                            <i class="bi bi-send me-1"></i> Submit Proposal
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<!-- Proposal submitted — success modal (auto-dismiss 3s) -->
<div class="modal fade" id="propose-success-modal" tabindex="-1" aria-hidden="true" aria-labelledby="propose-success-title">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body p-4">
                <i class="bi bi-check-circle-fill text-success d-block" style="font-size:3rem;line-height:1;"></i>
                <h5 class="mt-3 mb-2" id="propose-success-title">Proposal submitted</h5>
                <p class="text-muted mb-3" style="font-size:.92rem;">
                    Thanks — we'll review it and get back to you.
                </p>
                <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal" id="propose-success-close">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
