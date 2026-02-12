(function () {
    "use strict";

    /**
     * WPFeatureLoop Widget Controller
     *
     * Uses <template> tags for HTML - no inline strings!
     * Uses WordPress REST API for communication.
     *
     * Supports multiple instances on the same page.
     * All HTML (modals, templates, toast) lives inside the container div,
     * so all DOM queries are scoped via this.container.querySelector().
     */
    class WPFeatureLoopWidget {
        constructor(container, config) {
            this.config = config;
            this.projectId = config.project_id || "";
            this.container = container;
            this.features = [];
            this.currentCommentFeatureId = null;
            this.currentComments = [];

            // Translations from PHP
            this.t = config.i18n || {};

            // Template cache (survives innerHTML clearing)
            this.templates = {};

            // Modal/toast references (saved before innerHTML clearing)
            this.featureModal = null;
            this.commentModal = null;
            this.toast = null;

            // REST API base URL
            this.apiBase = config.rest_url || "/wp-json/wpfeatureloop/v1";
        }

        /**
         * Query an element inside the container
         */
        q(selector) {
            return this.container.querySelector(selector);
        }

        /**
         * Get a template element by ID (container-scoped)
         */
        getTemplate(id) {
            const fullId = `wfl-template-${id}`;
            if (!this.templates[fullId]) {
                const template = this.q(`#${fullId}`);
                if (!template) {
                    console.error(`WPFeatureLoop: Template not found: ${fullId}`);
                    return null;
                }
                this.templates[fullId] = template;
            }
            return this.templates[fullId];
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
            // Cache templates and modals before first render clears innerHTML
            this.cacheElements();

            this.loadFeatures();
        }

        /**
         * Cache template, modal and toast references
         *
         * Templates are <template> tags — once cached, cloneNode works
         * even after they're removed from DOM.
         * Modals and toast are saved so they can be re-appended after innerHTML clearing.
         * Modal listeners are attached here (once) since these elements persist.
         */
        cacheElements() {
            // Cache all templates
            const templateIds = ["card", "status", "empty", "error", "comment", "no-comments", "header", "skeleton"];
            templateIds.forEach((id) => this.getTemplate(id));

            // Save modals and toast (remove from DOM so innerHTML="" doesn't destroy them)
            this.featureModal = this.q("#wfl-modal");
            this.commentModal = this.q("#wfl-comment-modal");
            this.toast = this.q("#wfl-toast");

            if (this.featureModal) this.featureModal.remove();
            if (this.commentModal) this.commentModal.remove();
            if (this.toast) this.toast.remove();

            // Attach modal listeners once (these elements are never recreated)
            this.attachModalListeners();
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
            const sep = endpoint.includes("?") ? "&" : "?";
            const url = this.apiBase + endpoint + sep + "project_id=" + encodeURIComponent(this.projectId);

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

            // Clear container and rebuild
            this.container.innerHTML = "";
            this.container.appendChild(header);
            this.container.appendChild(list);

            // Re-append cached modals and toast
            if (this.featureModal) this.container.appendChild(this.featureModal);
            if (this.commentModal) this.container.appendChild(this.commentModal);
            if (this.toast) this.container.appendChild(this.toast);

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
                "in-progress": this.t.statusProgress || "In Progress",
                progress: this.t.statusProgress || "In Progress", // fallback
                completed: this.t.statusCompleted || "Completed",
                inbox: this.t.statusInbox || "Inbox",
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

            // Re-append cached modals and toast
            if (this.featureModal) this.container.appendChild(this.featureModal);
            if (this.commentModal) this.container.appendChild(this.commentModal);
            if (this.toast) this.container.appendChild(this.toast);

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
         * Attach modal listeners (called once from cacheElements)
         *
         * Modals persist across renders so listeners must only be added once.
         */
        attachModalListeners() {
            // Feature modal
            if (this.featureModal) {
                const modalClose = this.featureModal.querySelector("#wfl-modal-close");
                const modalCancel = this.featureModal.querySelector("#wfl-modal-cancel");
                const modalSubmit = this.featureModal.querySelector("#wfl-modal-submit");

                modalClose?.addEventListener("click", () => this.closeModal());
                modalCancel?.addEventListener("click", () => this.closeModal());
                this.featureModal.addEventListener("click", (e) => {
                    if (e.target === this.featureModal) this.closeModal();
                });
                modalSubmit?.addEventListener("click", () => this.handleSubmitFeature());
            }

            // Comment modal
            if (this.commentModal) {
                const commentClose = this.commentModal.querySelector("#wfl-comment-modal-close");

                commentClose?.addEventListener("click", () => this.closeCommentModal());
                this.commentModal.addEventListener("click", (e) => {
                    if (e.target === this.commentModal) this.closeCommentModal();
                });
            }
        }

        /**
         * Attach event listeners for rendered content (called each render)
         *
         * Only handles elements that are recreated on each render:
         * the add-feature button (from header template) and feature cards.
         */
        attachEventListeners() {
            // Add feature button (recreated from template each render)
            const addBtn = this.container.querySelector(".wfl-add-feature-btn");
            if (addBtn) {
                addBtn.addEventListener("click", () => this.openModal());
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
            if (this.featureModal) {
                this.featureModal.classList.add("wfl-active");
            }
        }

        /**
         * Close feature creation modal
         */
        closeModal() {
            if (this.featureModal) {
                this.featureModal.classList.remove("wfl-active");
                const titleInput = this.featureModal.querySelector("#wfl-feature-title");
                const descInput = this.featureModal.querySelector("#wfl-feature-desc");
                if (titleInput) titleInput.value = "";
                if (descInput) descInput.value = "";
            }
        }

        /**
         * Handle feature submission
         */
        async handleSubmitFeature() {
            const titleInput = this.featureModal?.querySelector("#wfl-feature-title");
            const descInput = this.featureModal?.querySelector("#wfl-feature-desc");
            const title = titleInput?.value.trim() || "";
            const description = descInput?.value.trim() || "";

            if (!title || !description) {
                this.showToast(this.t.fillAllFields || "Please fill in all fields", "error");
                return;
            }

            const submitBtn = this.featureModal?.querySelector("#wfl-modal-submit");
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

                    const list = this.container.querySelector(".wfl-list");
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
            const commentModal = this.commentModal;
            const commentsList = this.commentModal?.querySelector("#wfl-comments-list");
            const commentTitle = this.commentModal?.querySelector("#wfl-comment-title");
            const commentInput = this.commentModal?.querySelector("#wfl-comment-input");

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
            const commentsList = this.commentModal?.querySelector("#wfl-comments-list");

            const handleSubmit = async () => {
                const input = self.commentModal?.querySelector("#wfl-comment-input");
                const submitBtn = self.commentModal?.querySelector("#wfl-comment-submit");
                const text = input?.value.trim() || "";

                if (!text) return;

                if (submitBtn) submitBtn.disabled = true;

                try {
                    const newComment = await self.api("POST", `/features/${featureId}/comments`, {
                        text: text,
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
            const oldSubmit = this.commentModal?.querySelector("#wfl-comment-submit");
            const oldInput = this.commentModal?.querySelector("#wfl-comment-input");

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
            if (this.commentModal) {
                this.commentModal.classList.remove("wfl-active");

                const commentInput = this.commentModal.querySelector("#wfl-comment-input");
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
            if (!this.toast) return;

            this.toast.textContent = message;
            this.toast.className = "wfl-toast wfl-active";

            if (type === "success") {
                this.toast.classList.add("wfl-toast-success");
            } else if (type === "error") {
                this.toast.classList.add("wfl-toast-error");
            }

            setTimeout(() => {
                this.toast.classList.remove("wfl-active");
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

    // Auto-init: find all containers and create widget instances
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".wfl-container").forEach(function (el) {
            try {
                var config = JSON.parse(el.dataset.config || "{}");
                new WPFeatureLoopWidget(el, config).init();
            } catch (e) {
                console.error("WPFeatureLoop: Failed to init widget", e);
            }
        });
    });
})();
