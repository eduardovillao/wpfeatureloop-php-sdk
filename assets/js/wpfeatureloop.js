(function () {
    "use strict";

    /**
     * WPFeatureLoop Widget Controller
     */
    class WPFeatureLoopWidget {
        constructor(config) {
            this.config = config;
            this.container = null;
            this.features = [];
            this.currentCommentFeatureId = null;
            this.currentComments = [];

            // Translations from PHP
            this.t = config.i18n || {};

            // Icons from PHP
            this.icons = config.icons || {};
        }

        /**
         * Initialize the widget
         */
        init() {
            this.container = document.getElementById(this.config.container_id);
            if (!this.container) {
                console.error("WPFeatureLoop: Container not found");
                return;
            }

            this.loadFeatures();
        }

        /**
         * Load features from server
         */
        async loadFeatures() {
            try {
                const response = await this.ajax("get_features");

                if (response.success) {
                    this.features = response.data.features || [];
                    this.render();
                } else {
                    this.renderError();
                }
            } catch (error) {
                console.error("WPFeatureLoop: Failed to load features", error);
                this.renderError();
            }
        }

        /**
         * Make AJAX request
         */
        async ajax(action, data = {}) {
            const formData = new FormData();
            formData.append("action", "wpfeatureloop_" + action);
            formData.append("nonce", this.config.nonce);

            Object.keys(data).forEach((key) => {
                formData.append(key, data[key]);
            });

            const response = await fetch(this.config.ajax_url, {
                method: "POST",
                body: formData,
                credentials: "same-origin",
            });

            return response.json();
        }

        /**
         * Main render method
         */
        render() {
            const featuresHtml =
                this.features.length > 0
                    ? this.features.map((f) => this.renderCard(f)).join("")
                    : this.renderEmpty();

            const canCreate = this.config.can_interact;
            const addButton = canCreate
                ? `
          <button class="wfl-btn wfl-btn-primary wfl-ripple" id="wfl-add-feature">
            ${this.icons.plus || ""}
            ${this.t.suggestFeature || "Suggest Feature"}
          </button>
        `
                : "";

            this.container.innerHTML = `
        <div class="wfl-header">
          <div class="wfl-header-content">
            <h1 class="wfl-title">${this.t.title || "What's Next?"}</h1>
            <p class="wfl-subtitle">${this.t.subtitle || "Help us build what matters to you"}</p>
          </div>
          ${addButton}
        </div>
        <div class="wfl-list" id="wfl-list">
          ${featuresHtml}
        </div>
        ${this.renderModal()}
        ${this.renderCommentModal()}
        <div class="wfl-toast" id="wfl-toast"></div>
      `;

            this.container.removeAttribute("data-loading");
            this.attachEventListeners();
        }

        /**
         * Render a feature card
         */
        renderCard(feature) {
            const voteClass =
                feature.votes > 0
                    ? "wfl-vote-positive"
                    : feature.votes < 0
                      ? "wfl-vote-negative"
                      : "";
            const upVoted = feature.userVote === "up";
            const downVoted = feature.userVote === "down";
            const commentText =
                feature.commentsCount === 1
                    ? this.t.comment || "comment"
                    : this.t.comments || "comments";

            return `
        <div class="wfl-card" data-id="${feature.id}">
          <div class="wfl-vote">
            <button class="wfl-vote-btn wfl-vote-up wfl-tooltip ${upVoted ? "wfl-voted" : ""}"
                    data-id="${feature.id}"
                    data-action="up"
                    data-tooltip="${this.t.upvote || "Upvote"}">
              ${this.icons.arrowUp || ""}
            </button>
            <span class="wfl-vote-count ${voteClass}" data-id="${feature.id}">${feature.votes}</span>
            <button class="wfl-vote-btn wfl-vote-down wfl-tooltip ${downVoted ? "wfl-voted" : ""}"
                    data-id="${feature.id}"
                    data-action="down"
                    data-tooltip="${this.t.downvote || "Downvote"}">
              ${this.icons.arrowDown || ""}
            </button>
          </div>
          <div class="wfl-content">
            <div class="wfl-content-header">
              <h3 class="wfl-feature-title" data-id="${feature.id}">${feature.title}</h3>
              ${this.renderStatus(feature.status)}
            </div>
            <p class="wfl-description">${feature.description || ""}</p>
            <div class="wfl-footer">
              <button class="wfl-meta wfl-comment-trigger" data-id="${feature.id}">
                ${this.icons.comment || ""}
                <span>${feature.commentsCount} ${commentText}</span>
              </button>
            </div>
          </div>
        </div>
      `;
        }

        /**
         * Render status badge
         */
        renderStatus(status) {
            const labels = {
                open: this.t.statusOpen || "Open",
                planned: this.t.statusPlanned || "Planned",
                progress: this.t.statusProgress || "In Progress",
                completed: this.t.statusCompleted || "Completed",
            };

            return `
        <span class="wfl-status wfl-status-${status}">
          <span class="wfl-status-dot"></span>
          ${labels[status] || status}
        </span>
      `;
        }

        /**
         * Render empty state
         */
        renderEmpty() {
            return `
        <div class="wfl-empty">
          <div class="wfl-empty-icon">${this.icons.empty || ""}</div>
          <h3 class="wfl-empty-title">${this.t.emptyTitle || "No features yet"}</h3>
          <p class="wfl-empty-text">${this.t.emptyText || "Be the first to suggest a feature!"}</p>
        </div>
      `;
        }

        /**
         * Render error state
         */
        renderError() {
            this.container.innerHTML = `
        <div class="wfl-header">
          <div class="wfl-header-content">
            <h1 class="wfl-title">${this.t.title || "What's Next?"}</h1>
            <p class="wfl-subtitle">${this.t.subtitle || "Help us build what matters to you"}</p>
          </div>
        </div>
        <div class="wfl-error">
          <div class="wfl-error-icon">${this.icons.error || ""}</div>
          <h3 class="wfl-error-title">${this.t.errorTitle || "Failed to load features"}</h3>
          <p class="wfl-error-text">${this.t.errorText || "Please try again later."}</p>
          <button class="wfl-btn wfl-btn-primary" id="wfl-retry">
            ${this.t.retry || "Retry"}
          </button>
        </div>
      `;

            this.container
                .querySelector("#wfl-retry")
                ?.addEventListener("click", () => this.loadFeatures());
        }

        /**
         * Render feature creation modal
         */
        renderModal() {
            if (!this.config.can_interact) return "";

            return `
        <div class="wfl-modal-overlay" id="wfl-modal">
          <div class="wfl-modal">
            <div class="wfl-modal-header">
              <h2 class="wfl-modal-title">${this.t.suggestTitle || "Suggest a Feature"}</h2>
              <button class="wfl-modal-close" id="wfl-modal-close">
                ${this.icons.close || ""}
              </button>
            </div>
            <div class="wfl-modal-body">
              <div class="wfl-form-group">
                <label class="wfl-label" for="wfl-feature-title">${this.t.titleLabel || "Title"}</label>
                <input type="text" class="wfl-input" id="wfl-feature-title" placeholder="${this.t.titlePlaceholder || "Brief description of your feature idea"}">
              </div>
              <div class="wfl-form-group">
                <label class="wfl-label" for="wfl-feature-desc">${this.t.descriptionLabel || "Description"}</label>
                <textarea class="wfl-textarea" id="wfl-feature-desc" placeholder="${this.t.descriptionPlaceholder || "Explain the feature and why it would be valuable..."}"></textarea>
              </div>
            </div>
            <div class="wfl-modal-footer">
              <button class="wfl-btn wfl-btn-secondary" id="wfl-modal-cancel">${this.t.cancel || "Cancel"}</button>
              <button class="wfl-btn wfl-btn-primary wfl-ripple" id="wfl-modal-submit">${this.t.submit || "Submit Feature"}</button>
            </div>
          </div>
        </div>
      `;
        }

        /**
         * Render comments modal
         */
        renderCommentModal() {
            return `
        <div class="wfl-modal-overlay" id="wfl-comment-modal">
          <div class="wfl-modal">
            <div class="wfl-modal-header">
              <h2 class="wfl-modal-title" id="wfl-comment-title">${this.t.comments || "Comments"}</h2>
              <button class="wfl-modal-close" id="wfl-comment-modal-close">
                ${this.icons.close || ""}
              </button>
            </div>
            <div class="wfl-modal-body">
              <div class="wfl-comments-list" id="wfl-comments-list"></div>
              <div class="wfl-comment-input-wrapper">
                <input type="text" class="wfl-comment-input" id="wfl-comment-input" placeholder="${this.t.addComment || "Add a comment..."}">
                <button class="wfl-comment-submit" id="wfl-comment-submit">
                  ${this.icons.send || ""}
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
        }

        /**
         * Render comments list
         */
        renderCommentsList(comments) {
            if (comments.length === 0) {
                return `<p style="text-align: center; color: var(--wfl-gray-500); padding: 20px;">${this.t.noComments || "No comments yet."}</p>`;
            }

            return comments
                .map(
                    (c) => `
          <div class="wfl-comment${c.isTeamReply ? " wfl-comment-team" : ""}">
            <div class="wfl-comment-avatar${c.isTeamReply ? " wfl-comment-avatar-team" : ""}">${c.initials || "?"}</div>
            <div class="wfl-comment-content${c.isTeamReply ? " wfl-comment-content-team" : ""}">
              <div class="wfl-comment-header">
                <span class="wfl-comment-author">${c.author || "Anonymous"}</span>
                ${c.isTeamReply ? '<span class="wfl-comment-team-badge">Team</span>' : ""}
                <span class="wfl-comment-time">${c.time || ""}</span>
              </div>
              <p class="wfl-comment-text">${c.text || ""}</p>
            </div>
          </div>
        `,
                )
                .join("");
        }

        /**
         * Attach event listeners
         */
        attachEventListeners() {
            // Add feature button
            const addBtn = this.container.querySelector("#wfl-add-feature");
            if (addBtn) {
                addBtn.addEventListener("click", () => this.openModal());
            }

            // Modal events
            const modal = this.container.querySelector("#wfl-modal");
            if (modal) {
                const modalClose =
                    this.container.querySelector("#wfl-modal-close");
                const modalCancel =
                    this.container.querySelector("#wfl-modal-cancel");
                const modalSubmit =
                    this.container.querySelector("#wfl-modal-submit");

                modalClose?.addEventListener("click", () => this.closeModal());
                modalCancel?.addEventListener("click", () => this.closeModal());
                modal.addEventListener("click", (e) => {
                    if (e.target === modal) this.closeModal();
                });
                modalSubmit?.addEventListener("click", () =>
                    this.handleSubmitFeature(),
                );
            }

            // Comment modal events
            const commentModal =
                this.container.querySelector("#wfl-comment-modal");
            if (commentModal) {
                const commentClose = this.container.querySelector(
                    "#wfl-comment-modal-close",
                );

                commentClose?.addEventListener("click", () =>
                    this.closeCommentModal(),
                );
                commentModal.addEventListener("click", (e) => {
                    if (e.target === commentModal) this.closeCommentModal();
                });
            }

            // Card events
            this.features.forEach((f) => this.attachCardListeners(f.id));
        }

        /**
         * Attach listeners to a specific card
         */
        attachCardListeners(featureId) {
            const card = this.container.querySelector(
                `.wfl-card[data-id="${featureId}"]`,
            );
            if (!card) return;

            // Vote buttons
            card.querySelectorAll(".wfl-vote-btn").forEach((btn) => {
                btn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    this.handleVote(btn);
                });
            });

            // Comment trigger
            const commentTrigger = card.querySelector(".wfl-comment-trigger");
            if (commentTrigger) {
                commentTrigger.addEventListener("click", () => {
                    this.openCommentModal(featureId);
                });
            }
        }

        /**
         * Open feature creation modal
         */
        openModal() {
            const modal = this.container.querySelector("#wfl-modal");
            if (modal) {
                modal.classList.add("wfl-active");
            }
        }

        /**
         * Close feature creation modal
         */
        closeModal() {
            const modal = this.container.querySelector("#wfl-modal");
            if (modal) {
                modal.classList.remove("wfl-active");
                this.container.querySelector("#wfl-feature-title").value = "";
                this.container.querySelector("#wfl-feature-desc").value = "";
            }
        }

        /**
         * Handle feature submission
         */
        async handleSubmitFeature() {
            const title = this.container
                .querySelector("#wfl-feature-title")
                .value.trim();
            const description = this.container
                .querySelector("#wfl-feature-desc")
                .value.trim();

            if (!title || !description) {
                this.showToast(
                    this.t.fillAllFields || "Please fill in all fields",
                    "error",
                );
                return;
            }

            const submitBtn = this.container.querySelector("#wfl-modal-submit");
            submitBtn.disabled = true;

            try {
                const response = await this.ajax("create_feature", {
                    title,
                    description,
                });

                if (response.success) {
                    const newFeature = response.data;
                    this.features.unshift(newFeature);

                    const list = this.container.querySelector("#wfl-list");
                    const emptyEl = list.querySelector(".wfl-empty");
                    if (emptyEl) {
                        emptyEl.remove();
                    }

                    list.insertAdjacentHTML(
                        "afterbegin",
                        this.renderCard(newFeature),
                    );
                    this.attachCardListeners(newFeature.id);

                    this.closeModal();
                    this.showToast(
                        this.t.featureSubmitted ||
                            "Feature submitted successfully!",
                        "success",
                    );
                } else {
                    this.showToast(
                        response.data?.message ||
                            this.t.errorText ||
                            "Please try again later.",
                        "error",
                    );
                }
            } catch (error) {
                console.error("WPFeatureLoop: Failed to create feature", error);
                this.showToast(
                    this.t.errorText || "Please try again later.",
                    "error",
                );
            } finally {
                submitBtn.disabled = false;
            }
        }

        /**
         * Open comments modal
         */
        async openCommentModal(featureId) {
            const feature = this.features.find((f) => f.id === featureId);
            if (!feature) return;

            this.currentCommentFeatureId = featureId;
            const commentModal =
                this.container.querySelector("#wfl-comment-modal");
            const commentsList =
                this.container.querySelector("#wfl-comments-list");
            const commentTitle =
                this.container.querySelector("#wfl-comment-title");
            const commentInput =
                this.container.querySelector("#wfl-comment-input");

            // Clear input and show modal
            commentInput.value = "";
            commentTitle.textContent = feature.title;
            commentsList.innerHTML =
                '<div class="wfl-skeleton" style="height: 60px; margin-bottom: 12px;"></div>'.repeat(
                    2,
                );
            commentModal.classList.add("wfl-active");

            try {
                const response = await this.ajax("get_comments", {
                    feature_id: featureId,
                });

                if (response.success) {
                    this.currentComments = response.data.comments || [];
                    commentsList.innerHTML = this.renderCommentsList(
                        this.currentComments,
                    );
                    this.attachCommentListeners(featureId, feature);
                } else {
                    commentsList.innerHTML = `<p style="text-align: center; color: var(--wfl-danger);">${this.t.errorText || "Please try again later."}</p>`;
                }
            } catch (error) {
                console.error("WPFeatureLoop: Failed to load comments", error);
                commentsList.innerHTML = `<p style="text-align: center; color: var(--wfl-danger);">${this.t.errorText || "Please try again later."}</p>`;
            }
        }

        /**
         * Attach comment submission listeners
         */
        attachCommentListeners(featureId, feature) {
            const self = this;
            const commentsList =
                this.container.querySelector("#wfl-comments-list");

            const handleSubmit = async () => {
                const input =
                    self.container.querySelector("#wfl-comment-input");
                const submitBtn = self.container.querySelector(
                    "#wfl-comment-submit",
                );
                const text = input.value.trim();

                if (!text) return;

                submitBtn.disabled = true;

                try {
                    const response = await self.ajax("add_comment", {
                        feature_id: featureId,
                        content: text,
                    });

                    if (response.success) {
                        const newComment = response.data;
                        self.currentComments.push(newComment);
                        commentsList.innerHTML = self.renderCommentsList(
                            self.currentComments,
                        );

                        // Update comment count on card
                        feature.commentsCount = self.currentComments.length;
                        const card = self.container.querySelector(
                            `.wfl-card[data-id="${featureId}"]`,
                        );
                        const commentTrigger = card?.querySelector(
                            ".wfl-comment-trigger span",
                        );
                        if (commentTrigger) {
                            const commentText =
                                feature.commentsCount === 1
                                    ? self.t.comment || "comment"
                                    : self.t.comments || "comments";
                            commentTrigger.textContent = `${feature.commentsCount} ${commentText}`;
                        }

                        input.value = "";
                        self.showToast(
                            self.t.commentAdded || "Comment added!",
                            "success",
                        );
                    } else {
                        self.showToast(
                            response.data?.message ||
                                self.t.errorText ||
                                "Please try again later.",
                            "error",
                        );
                    }
                } catch (error) {
                    console.error(
                        "WPFeatureLoop: Failed to add comment",
                        error,
                    );
                    self.showToast(
                        self.t.errorText || "Please try again later.",
                        "error",
                    );
                } finally {
                    submitBtn.disabled = false;
                }
            };

            // Replace elements to remove old listeners
            const oldSubmit = this.container.querySelector(
                "#wfl-comment-submit",
            );
            const oldInput = this.container.querySelector("#wfl-comment-input");

            const newSubmit = oldSubmit.cloneNode(true);
            oldSubmit.parentNode.replaceChild(newSubmit, oldSubmit);
            newSubmit.addEventListener("click", handleSubmit);

            const newInput = oldInput.cloneNode(true);
            oldInput.parentNode.replaceChild(newInput, oldInput);
            newInput.addEventListener("keypress", (e) => {
                if (e.key === "Enter") handleSubmit();
            });
        }

        /**
         * Close comments modal
         */
        closeCommentModal() {
            const commentModal =
                this.container.querySelector("#wfl-comment-modal");
            if (commentModal) {
                commentModal.classList.remove("wfl-active");

                const commentInput =
                    this.container.querySelector("#wfl-comment-input");
                if (commentInput) {
                    commentInput.value = "";
                }

                this.currentCommentFeatureId = null;
                this.currentComments = [];
            }
        }

        /**
         * Handle vote
         */
        async handleVote(btn) {
            const id = btn.dataset.id;
            const action = btn.dataset.action;
            const feature = this.features.find(
                (f) => String(f.id) === String(id),
            );

            if (!feature) return;

            const card = this.container.querySelector(
                `.wfl-card[data-id="${id}"]`,
            );
            const voteCount = card.querySelector(".wfl-vote-count");
            const upBtn = card.querySelector(".wfl-vote-up");
            const downBtn = card.querySelector(".wfl-vote-down");

            // Disable buttons during request
            upBtn.disabled = true;
            downBtn.disabled = true;

            // Store original values for rollback
            const originalVotes = feature.votes;
            const originalUserVote = feature.userVote;

            // Calculate new vote state
            let newVoteType = "none";
            let voteDelta = 0;

            if (action === "up") {
                if (feature.userVote === "up") {
                    voteDelta = -1;
                    newVoteType = "none";
                } else if (feature.userVote === "down") {
                    voteDelta = 2;
                    newVoteType = "up";
                } else {
                    voteDelta = 1;
                    newVoteType = "up";
                }
            } else {
                if (feature.userVote === "down") {
                    voteDelta = 1;
                    newVoteType = "none";
                } else if (feature.userVote === "up") {
                    voteDelta = -2;
                    newVoteType = "down";
                } else {
                    voteDelta = -1;
                    newVoteType = "down";
                }
            }

            // Optimistic update
            feature.votes += voteDelta;
            feature.userVote = newVoteType === "none" ? null : newVoteType;

            // Update UI
            this.updateVoteUI(card, feature);

            // Animation
            voteCount.classList.add("wfl-animating");
            setTimeout(() => voteCount.classList.remove("wfl-animating"), 300);

            // Confetti on upvote
            if (action === "up" && newVoteType === "up") {
                this.createConfetti(upBtn);
            }

            try {
                const response = await this.ajax("vote", {
                    feature_id: id,
                    vote: newVoteType,
                });

                if (response.success) {
                    // Sync with server response
                    feature.votes = response.data.totalVotes;
                    feature.userVote = response.data.vote;
                    this.updateVoteUI(card, feature);
                } else {
                    // Revert on error
                    feature.votes = originalVotes;
                    feature.userVote = originalUserVote;
                    this.updateVoteUI(card, feature);
                    this.showToast(
                        this.t.errorText || "Please try again later.",
                        "error",
                    );
                }
            } catch (error) {
                console.error("WPFeatureLoop: Failed to save vote", error);
                // Revert on error
                feature.votes = originalVotes;
                feature.userVote = originalUserVote;
                this.updateVoteUI(card, feature);
                this.showToast(
                    this.t.errorText || "Please try again later.",
                    "error",
                );
            } finally {
                upBtn.disabled = false;
                downBtn.disabled = false;
            }
        }

        /**
         * Update vote UI
         */
        updateVoteUI(card, feature) {
            const voteCount = card.querySelector(".wfl-vote-count");
            const upBtn = card.querySelector(".wfl-vote-up");
            const downBtn = card.querySelector(".wfl-vote-down");

            upBtn.classList.toggle("wfl-voted", feature.userVote === "up");
            downBtn.classList.toggle("wfl-voted", feature.userVote === "down");
            voteCount.textContent = feature.votes;
            voteCount.classList.remove(
                "wfl-vote-positive",
                "wfl-vote-negative",
            );
            if (feature.votes > 0) {
                voteCount.classList.add("wfl-vote-positive");
            } else if (feature.votes < 0) {
                voteCount.classList.add("wfl-vote-negative");
            }
        }

        /**
         * Create confetti animation
         */
        createConfetti(element) {
            const colors = [
                "#3b82f6",
                "#2563eb",
                "#1d4ed8",
                "#10b981",
                "#f59e0b",
            ];
            const rect = element.getBoundingClientRect();

            for (let i = 0; i < 6; i++) {
                const confetti = document.createElement("div");
                confetti.className = "wfl-confetti";
                confetti.style.left = `${rect.left + rect.width / 2 + (Math.random() - 0.5) * 30}px`;
                confetti.style.top = `${rect.top + rect.height / 2}px`;
                confetti.style.background =
                    colors[Math.floor(Math.random() * colors.length)];
                confetti.style.position = "fixed";
                document.body.appendChild(confetti);

                setTimeout(() => confetti.remove(), 600);
            }
        }

        /**
         * Show toast notification
         */
        showToast(message, type = "default") {
            const toast = this.container.querySelector("#wfl-toast");
            if (!toast) return;

            toast.textContent = message;
            toast.className = "wfl-toast wfl-active";

            if (type === "success") {
                toast.classList.add("wfl-toast-success");
            } else if (type === "error") {
                toast.classList.add("wfl-toast-error");
            }

            setTimeout(() => {
                toast.classList.remove("wfl-active");
            }, 3000);
        }

        /**
         * Refresh features
         */
        async refresh() {
            this.container.setAttribute("data-loading", "true");
            await this.loadFeatures();
        }
    }

    // Expose to global scope
    window.WPFeatureLoopWidget = WPFeatureLoopWidget;

    // Auto-init if config is available
    if (typeof window.wpfeatureloop_config !== "undefined") {
        document.addEventListener("DOMContentLoaded", function () {
            const widget = new WPFeatureLoopWidget(window.wpfeatureloop_config);
            widget.init();
            window.wpfeatureloop = widget;
        });
    }
})();
