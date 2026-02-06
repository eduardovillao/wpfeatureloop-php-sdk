(function () {
    "use strict";

    /**
     * WPFeatureLoop Widget Controller
     *
     * Uses <template> tags for HTML - no inline strings!
     * Uses WordPress REST API for communication.
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

            // Template cache
            this.templates = {};

            // REST API base URL
            this.apiBase = config.rest_url || "/wp-json/wpfeatureloop/v1";
        }

        /**
         * Get a template element by ID
         */
        getTemplate(id) {
            if (!this.templates[id]) {
                const template = document.getElementById(`wfl-template-${id}`);
                if (!template) {
                    console.error(`WPFeatureLoop: Template not found: wfl-template-${id}`);
                    return null;
                }
                this.templates[id] = template;
            }
            return this.templates[id];
        }

        /**
         * Clone a template and return the element
         */
        cloneTemplate(id) {
            const template = this.getTemplate(id);
            if (!template) return null;
            return template.content.cloneNode(true).firstElementChild;
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
                const response = await this.api("GET", "/features");

                // API returns { features: [...] } via RestApi.php
                if (response.features) {
                    this.features = response.features;
                    this.render();
                } else if (Array.isArray(response)) {
                    // Fallback: direct array response
                    this.features = response;
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
         * Make REST API request
         */
        async api(method, endpoint, data = null) {
            const url = this.apiBase + endpoint;

            const options = {
                method,
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": this.config.nonce,
                },
                credentials: "same-origin",
            };

            if (data && (method === "POST" || method === "PUT" || method === "PATCH")) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(url, options);
            const json = await response.json();

            if (!response.ok) {
                throw new Error(json.error || "Request failed");
            }

            return json;
        }

        /**
         * Main render method
         */
        render() {
            // Clone header template
            const headerTemplate = this.getTemplate("header");
            const header = headerTemplate.content.cloneNode(true);

            // Show add button if can interact
            const addBtn = header.querySelector(".wfl-add-feature-btn");
            if (addBtn && this.config.can_interact) {
                addBtn.style.display = "";
                addBtn.removeAttribute("disabled");
            }

            // Create list container
            const list = document.createElement("div");
            list.className = "wfl-list";
            list.id = "wfl-list";

            // Add features or empty state
            if (this.features.length > 0) {
                this.features.forEach((f) => {
                    const card = this.createCard(f);
                    if (card) list.appendChild(card);
                });
            } else {
                const empty = this.cloneTemplate("empty");
                if (empty) list.appendChild(empty);
            }

            // Get modal templates from DOM (already rendered by PHP)
            const featureModal = document.getElementById("wfl-modal");
            const commentModal = document.getElementById("wfl-comment-modal");
            const toast = document.getElementById("wfl-toast");

            // Clear container and rebuild
            this.container.innerHTML = "";
            this.container.appendChild(header);
            this.container.appendChild(list);

            // Re-append modals and toast if they exist
            if (featureModal) this.container.appendChild(featureModal);
            if (commentModal) this.container.appendChild(commentModal);
            if (toast) this.container.appendChild(toast);

            this.container.removeAttribute("data-loading");
            this.attachEventListeners();
        }

        /**
         * Create a feature card element
         */
        createCard(feature) {
            const card = this.cloneTemplate("card");
            if (!card) return null;

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

            // Set data attributes
            card.dataset.id = feature.id;

            // Vote up button
            const upBtn = card.querySelector(".wfl-vote-up");
            upBtn.dataset.id = feature.id;
            upBtn.dataset.tooltip = this.t.upvote || "Upvote";
            if (upVoted) upBtn.classList.add("wfl-voted");

            // Vote count
            const voteCount = card.querySelector(".wfl-vote-count");
            voteCount.dataset.id = feature.id;
            voteCount.textContent = feature.votes;
            if (voteClass) voteCount.classList.add(voteClass);

            // Vote down button
            const downBtn = card.querySelector(".wfl-vote-down");
            downBtn.dataset.id = feature.id;
            downBtn.dataset.tooltip = this.t.downvote || "Downvote";
            if (downVoted) downBtn.classList.add("wfl-voted");

            // Title
            const title = card.querySelector(".wfl-feature-title");
            title.dataset.id = feature.id;
            title.textContent = feature.title;

            // Status
            const status = card.querySelector(".wfl-status");
            const statusLabel = status.querySelector(".wfl-status-label");
            status.className = `wfl-status wfl-status-${feature.status || "open"}`;
            statusLabel.textContent = this.getStatusLabel(feature.status || "open");

            // Description
            const desc = card.querySelector(".wfl-description");
            desc.textContent = feature.description || "";

            // Comments
            const commentTrigger = card.querySelector(".wfl-comment-trigger");
            commentTrigger.dataset.id = feature.id;
            const commentCount = card.querySelector(".wfl-comment-count");
            commentCount.textContent = `${feature.commentsCount} ${commentText}`;

            return card;
        }

        /**
         * Get status label
         */
        getStatusLabel(status) {
            const labels = {
                open: this.t.statusOpen || "Open",
                planned: this.t.statusPlanned || "Planned",
                progress: this.t.statusProgress || "In Progress",
                completed: this.t.statusCompleted || "Completed",
            };
            return labels[status] || status;
        }

        /**
         * Render error state
         */
        renderError() {
            // Clone header template
            const headerTemplate = this.getTemplate("header");
            const header = headerTemplate.content.cloneNode(true);

            // Clone error template
            const error = this.cloneTemplate("error");

            // Clear container and rebuild
            this.container.innerHTML = "";
            this.container.appendChild(header);
            if (error) {
                this.container.appendChild(error);
                // Attach retry listener
                const retryBtn = this.container.querySelector(".wfl-retry-btn");
                if (retryBtn) {
                    retryBtn.addEventListener("click", () => this.loadFeatures());
                }
            }

            this.container.removeAttribute("data-loading");
        }

        /**
         * Create a comment element
         */
        createComment(comment) {
            const el = this.cloneTemplate("comment");
            if (!el) return null;

            if (comment.isTeamReply) {
                el.classList.add("wfl-comment-team");
            }

            const avatar = el.querySelector(".wfl-comment-avatar");
            avatar.textContent = comment.initials || "?";
            if (comment.isTeamReply) {
                avatar.classList.add("wfl-comment-avatar-team");
            }

            const content = el.querySelector(".wfl-comment-content");
            if (comment.isTeamReply) {
                content.classList.add("wfl-comment-content-team");
            }

            const author = el.querySelector(".wfl-comment-author");
            author.textContent = comment.author || "Anonymous";

            const teamBadge = el.querySelector(".wfl-comment-team-badge");
            if (comment.isTeamReply && teamBadge) {
                teamBadge.style.display = "";
            }

            const time = el.querySelector(".wfl-comment-time");
            time.textContent = comment.time || "";

            const text = el.querySelector(".wfl-comment-text");
            text.textContent = comment.text || "";

            return el;
        }

        /**
         * Render comments list
         */
        renderCommentsList(comments, container) {
            container.innerHTML = "";

            if (comments.length === 0) {
                const noComments = this.cloneTemplate("no-comments");
                if (noComments) container.appendChild(noComments);
                return;
            }

            comments.forEach((c) => {
                const comment = this.createComment(c);
                if (comment) container.appendChild(comment);
            });
        }

        /**
         * Attach event listeners
         */
        attachEventListeners() {
            // Add feature button
            const addBtn = this.container.querySelector(".wfl-add-feature-btn");
            if (addBtn) {
                addBtn.addEventListener("click", () => this.openModal());
            }

            // Modal events
            const modal = this.container.querySelector("#wfl-modal");
            if (modal) {
                const modalClose = this.container.querySelector("#wfl-modal-close");
                const modalCancel = this.container.querySelector("#wfl-modal-cancel");
                const modalSubmit = this.container.querySelector("#wfl-modal-submit");

                modalClose?.addEventListener("click", () => this.closeModal());
                modalCancel?.addEventListener("click", () => this.closeModal());
                modal.addEventListener("click", (e) => {
                    if (e.target === modal) this.closeModal();
                });
                modalSubmit?.addEventListener("click", () => this.handleSubmitFeature());
            }

            // Comment modal events
            const commentModal = this.container.querySelector("#wfl-comment-modal");
            if (commentModal) {
                const commentClose = this.container.querySelector("#wfl-comment-modal-close");

                commentClose?.addEventListener("click", () => this.closeCommentModal());
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
            const card = this.container.querySelector(`.wfl-card[data-id="${featureId}"]`);
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
                const titleInput = this.container.querySelector("#wfl-feature-title");
                const descInput = this.container.querySelector("#wfl-feature-desc");
                if (titleInput) titleInput.value = "";
                if (descInput) descInput.value = "";
            }
        }

        /**
         * Handle feature submission
         */
        async handleSubmitFeature() {
            const titleInput = this.container.querySelector("#wfl-feature-title");
            const descInput = this.container.querySelector("#wfl-feature-desc");
            const title = titleInput?.value.trim() || "";
            const description = descInput?.value.trim() || "";

            if (!title || !description) {
                this.showToast(this.t.fillAllFields || "Please fill in all fields", "error");
                return;
            }

            const submitBtn = this.container.querySelector("#wfl-modal-submit");
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await this.api("POST", "/features", { title, description });

                this.closeModal();

                // Check if feature is pending moderation
                if (response.isPending) {
                    // Show message from API (feature goes to Inbox for review)
                    this.showToast(
                        response.message || this.t.featurePending || "Your suggestion has been submitted and will be reviewed.",
                        "success"
                    );
                } else {
                    // Feature is immediately visible (add to list)
                    this.features.unshift(response);

                    const list = this.container.querySelector("#wfl-list");
                    const emptyEl = list?.querySelector(".wfl-empty");
                    if (emptyEl) emptyEl.remove();

                    const newCard = this.createCard(response);
                    if (newCard && list) {
                        list.insertBefore(newCard, list.firstChild);
                        this.attachCardListeners(response.id);
                    }

                    this.showToast(
                        this.t.featureSubmitted || "Feature submitted successfully!",
                        "success"
                    );
                }
            } catch (error) {
                console.error("WPFeatureLoop: Failed to create feature", error);
                this.showToast(error.message || this.t.errorText || "Please try again later.", "error");
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        }

        /**
         * Open comments modal
         */
        async openCommentModal(featureId) {
            const feature = this.features.find((f) => f.id === featureId);
            if (!feature) return;

            this.currentCommentFeatureId = featureId;
            const commentModal = this.container.querySelector("#wfl-comment-modal");
            const commentsList = this.container.querySelector("#wfl-comments-list");
            const commentTitle = this.container.querySelector("#wfl-comment-title");
            const commentInput = this.container.querySelector("#wfl-comment-input");

            // Clear input and show modal
            if (commentInput) commentInput.value = "";
            if (commentTitle) commentTitle.textContent = feature.title;

            // Show loading skeleton
            if (commentsList) {
                const skeleton = this.cloneTemplate("skeleton");
                commentsList.innerHTML = "";
                if (skeleton) commentsList.appendChild(skeleton);
            }

            if (commentModal) commentModal.classList.add("wfl-active");

            try {
                const response = await this.api("GET", `/features/${featureId}/comments`);

                this.currentComments = response.comments || [];
                if (commentsList) {
                    this.renderCommentsList(this.currentComments, commentsList);
                }
                this.attachCommentListeners(featureId, feature);
            } catch (error) {
                console.error("WPFeatureLoop: Failed to load comments", error);
                if (commentsList) {
                    commentsList.innerHTML = `<p style="text-align: center; color: var(--wfl-danger);">${this.t.errorText || "Please try again later."}</p>`;
                }
            }
        }

        /**
         * Attach comment submission listeners
         */
        attachCommentListeners(featureId, feature) {
            const self = this;
            const commentsList = this.container.querySelector("#wfl-comments-list");

            const handleSubmit = async () => {
                const input = self.container.querySelector("#wfl-comment-input");
                const submitBtn = self.container.querySelector("#wfl-comment-submit");
                const text = input?.value.trim() || "";

                if (!text) return;

                if (submitBtn) submitBtn.disabled = true;

                try {
                    const newComment = await self.api("POST", `/features/${featureId}/comments`, {
                        content: text,
                    });

                    self.currentComments.push(newComment);
                    if (commentsList) {
                        self.renderCommentsList(self.currentComments, commentsList);
                    }

                    // Update comment count on card
                    feature.commentsCount = self.currentComments.length;
                    const card = self.container.querySelector(`.wfl-card[data-id="${featureId}"]`);
                    const commentCount = card?.querySelector(".wfl-comment-count");
                    if (commentCount) {
                        const commentText =
                            feature.commentsCount === 1
                                ? self.t.comment || "comment"
                                : self.t.comments || "comments";
                        commentCount.textContent = `${feature.commentsCount} ${commentText}`;
                    }

                    if (input) input.value = "";
                    self.showToast(self.t.commentAdded || "Comment added!", "success");
                } catch (error) {
                    console.error("WPFeatureLoop: Failed to add comment", error);
                    self.showToast(error.message || self.t.errorText || "Please try again later.", "error");
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            };

            // Replace elements to remove old listeners
            const oldSubmit = this.container.querySelector("#wfl-comment-submit");
            const oldInput = this.container.querySelector("#wfl-comment-input");

            if (oldSubmit) {
                const newSubmit = oldSubmit.cloneNode(true);
                oldSubmit.parentNode.replaceChild(newSubmit, oldSubmit);
                newSubmit.addEventListener("click", handleSubmit);
            }

            if (oldInput) {
                const newInput = oldInput.cloneNode(true);
                oldInput.parentNode.replaceChild(newInput, oldInput);
                newInput.addEventListener("keypress", (e) => {
                    if (e.key === "Enter") handleSubmit();
                });
            }
        }

        /**
         * Close comments modal
         */
        closeCommentModal() {
            const commentModal = this.container.querySelector("#wfl-comment-modal");
            if (commentModal) {
                commentModal.classList.remove("wfl-active");

                const commentInput = this.container.querySelector("#wfl-comment-input");
                if (commentInput) commentInput.value = "";

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
            const feature = this.features.find((f) => String(f.id) === String(id));

            if (!feature) return;

            const card = this.container.querySelector(`.wfl-card[data-id="${id}"]`);
            const voteCount = card?.querySelector(".wfl-vote-count");
            const upBtn = card?.querySelector(".wfl-vote-up");
            const downBtn = card?.querySelector(".wfl-vote-down");

            // Disable buttons during request
            if (upBtn) upBtn.disabled = true;
            if (downBtn) downBtn.disabled = true;

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
            if (card) this.updateVoteUI(card, feature);

            // Animation
            if (voteCount) {
                voteCount.classList.add("wfl-animating");
                setTimeout(() => voteCount.classList.remove("wfl-animating"), 300);
            }

            // Confetti on upvote
            if (action === "up" && newVoteType === "up" && upBtn) {
                this.createConfetti(upBtn);
            }

            try {
                const response = await this.api("POST", `/features/${id}/vote`, {
                    vote: newVoteType,
                });

                // Sync with server response
                if (response.totalVotes !== undefined) {
                    feature.votes = response.totalVotes;
                }
                if (response.vote !== undefined) {
                    feature.userVote = response.vote;
                }
                if (card) this.updateVoteUI(card, feature);
            } catch (error) {
                console.error("WPFeatureLoop: Failed to save vote", error);
                // Revert on error
                feature.votes = originalVotes;
                feature.userVote = originalUserVote;
                if (card) this.updateVoteUI(card, feature);
                this.showToast(error.message || this.t.errorText || "Please try again later.", "error");
            } finally {
                if (upBtn) upBtn.disabled = false;
                if (downBtn) downBtn.disabled = false;
            }
        }

        /**
         * Update vote UI
         */
        updateVoteUI(card, feature) {
            const voteCount = card.querySelector(".wfl-vote-count");
            const upBtn = card.querySelector(".wfl-vote-up");
            const downBtn = card.querySelector(".wfl-vote-down");

            if (upBtn) upBtn.classList.toggle("wfl-voted", feature.userVote === "up");
            if (downBtn) downBtn.classList.toggle("wfl-voted", feature.userVote === "down");

            if (voteCount) {
                voteCount.textContent = feature.votes;
                voteCount.classList.remove("wfl-vote-positive", "wfl-vote-negative");
                if (feature.votes > 0) {
                    voteCount.classList.add("wfl-vote-positive");
                } else if (feature.votes < 0) {
                    voteCount.classList.add("wfl-vote-negative");
                }
            }
        }

        /**
         * Create confetti animation
         */
        createConfetti(element) {
            const colors = ["#3b82f6", "#2563eb", "#1d4ed8", "#10b981", "#f59e0b"];
            const rect = element.getBoundingClientRect();

            for (let i = 0; i < 6; i++) {
                const confetti = document.createElement("div");
                confetti.className = "wfl-confetti";
                confetti.style.left = `${rect.left + rect.width / 2 + (Math.random() - 0.5) * 30}px`;
                confetti.style.top = `${rect.top + rect.height / 2}px`;
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
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

    // Auto-init when DOM is ready (config check inside to handle script loading order)
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof window.wpfeatureloop_config !== "undefined") {
            const widget = new WPFeatureLoopWidget(window.wpfeatureloop_config);
            widget.init();
            window.wpfeatureloop = widget;
        }
    });
})();
