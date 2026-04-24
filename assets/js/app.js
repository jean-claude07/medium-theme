// Document Ready init GSAP
document.addEventListener("DOMContentLoaded", () => {
  // GSAP plugins are registered via CDN script order but let's be safe
  if (typeof gsap !== "undefined" && typeof ScrollTrigger !== "undefined") {
    gsap.registerPlugin(ScrollTrigger);

    // Reading Progress Bar
    const progressBar = document.querySelector(".reading-progress-bar");
    if (progressBar) {
      gsap.to(progressBar, {
        scaleX: 1,
        ease: "none",
        scrollTrigger: {
          trigger: "body",
          start: "top top",
          end: "bottom bottom",
          scrub: 0.3,
        },
      });
    }

    // Fade In Animations
    const fadeElements = document.querySelectorAll(".animate-on-scroll");
    fadeElements.forEach((el) => {
      gsap.from(el, {
        y: 30,
        opacity: 0,
        duration: 0.8,
        ease: "power3.out",
        scrollTrigger: {
          trigger: el,
          start: "top 85%",
          toggleActions: "play none none reverse",
        },
      });
    });
  }
});

// Alpine JS Theme Toggle
document.addEventListener("alpine:init", () => {
  Alpine.data("themeState", () => ({
    darkMode:
      localStorage.getItem("theme") === "dark" ||
      (!("theme" in localStorage) &&
        window.matchMedia("(prefers-color-scheme: dark)").matches),
    toggleTheme() {
      this.darkMode = !this.darkMode;
      if (this.darkMode) {
        document.documentElement.classList.add("dark");
        localStorage.setItem("theme", "dark");
      } else {
        document.documentElement.classList.remove("dark");
        localStorage.setItem("theme", "light");
      }
    },
    init() {
      if (this.darkMode) {
        document.documentElement.classList.add("dark");
      }
    },
  }));

  Alpine.data("authModal", () => ({
    isOpen: false, // Utile si tu transformes ta page en popup plus tard
    tab: "login",
    loading: false,
    error: "",
    form: { email: "", password: "", name: "" },

    init() {
      const params = new URLSearchParams(window.location.search);
      if (params.get("tab") === "register") this.tab = "register";
    },

    async submit() {
      this.loading = true;
      this.error = "";

      const formData = new FormData();
      formData.append("action", "mc_auth_action");
      formData.append("tab", this.tab);
      formData.append("email", this.form.email);
      formData.append("password", this.form.password);
      formData.append("name", this.form.name);

      formData.append("security", mediumCloneData.auth_nonce);

      try {
        const res = await fetch(mediumCloneData.ajax_url, {
          method: "POST",
          body: formData,
        });

        const result = await res.json();

        if (result.success) {
          if (result.data && result.data.status === "pending") {
            // Inscription réussie mais activation requise
            this.tab = "login"; // On repasse sur login
            this.form.password = ""; // On vide le pass
            this.error = result.data.message; // On affiche le message dans la zone d'erreur (ou une autre zone de succès)
            // Note : Ici on utilise this.error pour plus de simplicité, mais on pourrait avoir une this.successMessage
          } else {
            window.location.href = result.data.redirect || mediumCloneData.root_url;
          }
        } else {
          this.error = result.data || "Une erreur est survenue";
        }
      } catch (err) {
        this.error = "Erreur réseau. Veuillez réessayer.";
      } finally {
        this.loading = false;
      }
    },
  }));

  Alpine.data("postEditor", () => ({
    isEditing: false,
    loading: false,
    message: "",
    isError: false,
    form: {
      id: null,
      title: "",
      content: "",
      youtube_url: "",
      social_link: "",
      featured_image: null,
      featured_image_preview: null,
      categories: [],
    },
    availableCategories: mediumCloneData.categories || [],
    quill: null,

    init() {
      this.quill = new Quill(this.$refs.editor, {
        theme: "bubble",
        placeholder: "Racontez votre histoire...",
        modules: {
          toolbar: [
            ["bold", "italic", "underline"],
            [{ header: 1 }, { header: 2 }],
            ["blockquote", "code-block", "link"],
          ],
        },
      });

      this.quill.on("text-change", () => {
        this.form.content = this.quill.root.innerHTML;
      });
    },

    editPost(post) {
      this.form.id = post.id;
      this.form.title = post.title;
      this.form.content = post.content;
      this.form.youtube_url = post.youtube;
      this.form.social_link = post.social;
      this.quill.root.innerHTML = post.content;
      this.form.content = post.content;
      this.form.categories = post.categories || [];

      this.isEditing = true;
      window.scrollTo({ top: 0, behavior: "smooth" });
    },

    insertEmbedPrompt(type) {
      // 1. Des messages de prompt plus clairs en fonction du type
      const promptMessage =
        type === "youtube"
          ? "Collez l'URL de la vidéo YouTube (ou Short) ici :"
          : "Collez l'URL du post (Facebook, LinkedIn, X, Instagram...) ici :";

      const url = prompt(promptMessage);

      if (!url) return;
      if (!url.startsWith("http")) {
        alert("Veuillez entrer une URL valide commençant par http ou https.");
        return;
      }

      let embedHtml = "";

      if (type === "youtube") {
        let cleanUrl = url.replace("/shorts/", "/watch?v=");
        const regExp =
          /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = cleanUrl.match(regExp);
        const videoId = match && match[2].length === 11 ? match[2] : null;

        if (videoId) {
          this.form.youtube_url = cleanUrl;
          embedHtml = `
            <div class="my-6 aspect-video bg-gray-900 rounded-xl overflow-hidden shadow-lg border border-gray-100 dark:border-gray-800">
                <iframe src="https://www.youtube.com/embed/${videoId}" 
                    class="w-full h-full" 
                    frameborder="0" 
                    loading="lazy"
                    title="Lecteur vidéo YouTube"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div><p><br></p>`;
          this.message = "Vidéo YouTube insérée !";
          setTimeout(() => (this.message = ""), 3000);
        } else {
          alert("L'URL YouTube ne semble pas valide. Vérifiez le lien.");
          return;
        }
      } else {
        this.form.social_link = url;
        let platformName = "Médias sociaux";
        let platformColor = "text-primary";
        const lowerUrl = url.toLowerCase();

        if (lowerUrl.includes("facebook.com")) {
          platformName = "Post Facebook";
          platformColor = "text-blue-600";
        } else if (lowerUrl.includes("linkedin.com")) {
          platformName = "Post LinkedIn";
          platformColor = "text-blue-700";
        } else if (lowerUrl.includes("instagram.com")) {
          platformName = "Post Instagram";
          platformColor = "text-pink-600";
        } else if (
          lowerUrl.includes("twitter.com") ||
          lowerUrl.includes("x.com")
        ) {
          platformName = "Post X (Twitter)";
          platformColor = "text-gray-900 dark:text-gray-100";
        }

        embedHtml = `
        <div class="my-6 p-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl text-center bg-gray-50/50 dark:bg-dark-surface/50 hover:bg-gray-100 dark:hover:bg-dark-surface transition-colors group">
            <div class="${platformColor} mb-3 flex justify-center transform group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
            </div>
            <p class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-2">Contenu ${platformName}</p>
            <a href="${url}" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-500 hover:text-blue-600 underline break-all line-clamp-2">${url}</a>
        </div><p><br></p>`;
        this.message = "Lien social inséré !";
        setTimeout(() => (this.message = ""), 3000);
      }

      if (embedHtml) {
        this.quill.focus();
        const range = this.quill.getSelection(true);
        const index = range ? range.index : this.quill.getLength();

        this.quill.clipboard.dangerouslyPasteHTML(index, embedHtml);

        setTimeout(() => {
          this.quill.setSelection(index + 2, 0);
        }, 150);
      }
    },

    toggleEdit() {
      this.isEditing = !this.isEditing;
      this.message = "";
      this.isError = false;
      if (!this.isEditing) {
        this.form = {
          id: null,
          title: "",
          content: "",
          youtube_url: "",
          social_link: "",
          featured_image: null,
          featured_image_preview: null,
          categories: [],
        };
        this.quill.setContents([]);
      }
    },

    handleFileUpload(event) {
      const file = event.target.files[0];
      if (file) {
        this.form.featured_image = file;

        const reader = new FileReader();
        reader.onload = (e) => {
          this.form.featured_image_preview = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    },

    toggleCategory(id) {
      if (this.form.categories.includes(id)) {
        this.form.categories = this.form.categories.filter((c) => c !== id);
      } else {
        if (this.form.categories.length < 2) {
          this.form.categories.push(id);
        } else {
          this.isError = true;
          this.message =
            "Vous ne pouvez sélectionner que 2 catégories maximum.";
          setTimeout(() => {
            this.message = "";
            this.isError = false;
          }, 3000);
        }
      }
    },

    async savePost(status = "draft") {
      if (!this.form.title || !this.form.content) {
        this.isError = true;
        this.message = "Title and content are required.";
        return;
      }

      this.loading = true;
      this.message = "Saving...";
      this.isError = false;

      const formData = new FormData();
      if (this.form.id) {
        formData.append("post_id", this.form.id);
      }
      formData.append("title", this.form.title);
      formData.append("content", this.form.content);
      formData.append("status", status);
      formData.append("youtube_url", this.form.youtube_url);
      formData.append("social_link", this.form.social_link);
      this.form.categories.forEach((catId) => {
        formData.append("categories[]", catId);
      });

      if (this.form.featured_image) {
        formData.append("featured_image", this.form.featured_image);
      }

      try {
        const res = await fetch(
          mediumCloneData.rest_url + "mediumclone/v1/posts",
          {
            method: "POST",
            headers: {
              "X-WP-Nonce": mediumCloneData.nonce,
            },
            body: formData,
          },
        );

        const data = await res.json();
        if (res.ok && data.status === "success") {
          this.isError = false;
          this.message = "Post saved successfully!";
          setTimeout(() => window.location.reload(), 1000);
        } else {
          throw new Error(data.message || "Error saving post");
        }
      } catch (e) {
        this.isError = true;
        this.message = e.message;
      } finally {
        this.loading = false;
      }
    },
  }));

  Alpine.data("profileEditor", () => ({
    loading: false,
    message: "",
    isError: false,
    avatarPreview: null,
    avatarFile: null,
    showCropper: false,
    cropper: null,
    form: {
      first_name: "",
      last_name: "",
      display_name: "",
      bio: "",
      twitter: "",
      linkedin: "",
      facebook: "",
      website: "",
      avatar: "",
    },

    async init() {
      try {
        const res = await fetch(
          mediumCloneData.rest_url + "mediumclone/v1/profile",
          {
            headers: { "X-WP-Nonce": mediumCloneData.nonce },
          },
        );
        if (res.ok) {
          const data = await res.json();
          this.form = data;
        }
      } catch (e) {
        console.error("Erreur chargement profil", e);
      }
    },

    handleAvatarUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      if (file.size > 2 * 1024 * 1024) {
        this.isError = true;
        this.message = "L'image est trop lourde (max 2Mo).";
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        this.showCropper = true;

        this.$nextTick(() => {
          const img = this.$refs.cropperImg;
          if (!img) return;

          // Scroll the crop panel into view smoothly
          img
            .closest("[x-show]")
            ?.scrollIntoView({ behavior: "smooth", block: "nearest" });

          img.src = e.target.result;
          img.onload = () => {
            if (this.cropper) this.cropper.destroy();

            this.cropper = new Cropper(img, {
              aspectRatio: 1,
              viewMode: 1,
              dragMode: "move",
              autoCropArea: 1,
              background: false,
            });
          };
        });
      };
      reader.readAsDataURL(file);
    },

    applyCrop() {
      if (!this.cropper) return;

      this.cropper
        .getCroppedCanvas({
          width: 400,
          height: 400,
        })
        .toBlob(
          (blob) => {
            this.avatarFile = blob;
            if (this.avatarPreview) URL.revokeObjectURL(this.avatarPreview);
            this.avatarPreview = URL.createObjectURL(blob);

            this.closeCropper();
          },
          "image/jpeg",
          0.9,
        );
    },

    closeCropper() {
      this.showCropper = false;

      if (this.cropper) {
        this.cropper.destroy();
        this.cropper = null;
      }

      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = "";
      }
    },

    async saveProfile() {
      this.loading = true;
      this.message = "Enregistrement...";
      this.isError = false;

      const formData = new FormData();
      formData.append("first_name", this.form.first_name);
      formData.append("last_name", this.form.last_name);
      formData.append("display_name", this.form.display_name);
      formData.append("bio", this.form.bio);
      formData.append("twitter", this.form.twitter);
      formData.append("linkedin", this.form.linkedin);
      formData.append("facebook", this.form.facebook);
      formData.append("website", this.form.website);

      if (this.avatarFile) {
        formData.append("avatar_file", this.avatarFile, "avatar.jpg");
      }

      try {
        const res = await fetch(
          mediumCloneData.rest_url + "mediumclone/v1/profile",
          {
            method: "POST",
            headers: {
              "X-WP-Nonce": mediumCloneData.nonce,
            },
            body: formData,
          },
        );

        const data = await res.json();
        if (res.ok) {
          this.message = "Profil mis à jour !";
          // Si on a uploadé une image, on rafraîchit pour voir l'avatar partout
          if (this.avatarFile) {
            setTimeout(() => window.location.reload(), 1000);
          }
        } else {
          throw new Error(data.message || "Erreur lors de la mise à jour");
        }
      } catch (e) {
        this.isError = true;
        this.message = e.message;
      } finally {
        this.loading = false;
      }
    },
  }));

  Alpine.data("postPage", (postId, initialCount) => ({
    ...reactions(postId),
    ...commentSystem(postId, initialCount),
  }));

  Alpine.data("commentSystem", (postId, initialCount) => ({
    count: initialCount,
    loading: false,
    open: window.location.hash.includes("comment"),

    init() {
      // Handle page load hash
      if (this.open) {
        this.scrollToTarget();
      }

      // Handle click on reply and pagination links
      document.addEventListener("click", (e) => {
        const replyLink = e.target.closest(".comment-reply-link");
        if (replyLink) {
          this.open = true;
          this.scrollToTarget();
        }

        const pagLink = e.target.closest(".comment-navigation a, .nav-links a");
        if (pagLink) {
          e.preventDefault();
          const url = new URL(pagLink.href);
          const page = url.searchParams.get("cpage") || 1;
          this.loadComments(page);
        }
      });

      // Handle hash changes
      window.addEventListener("hashchange", () => {
        if (window.location.hash.includes("comment")) {
          this.open = true;
          this.scrollToTarget();
        }
      });
    },

    async loadComments(page) {
      if (this.loading) return;
      this.loading = true;

      const formData = new FormData();
      formData.append("action", "mc_ajax_load_comments");
      formData.append("post_id", postId);
      formData.append("page", page);

      try {
        const response = await fetch(mediumCloneData.ajax_url, {
          method: "POST",
          body: formData,
        });

        const result = await response.json();

        if (result.success) {
          const container = this.$refs.commentListContainer;
          if (container) {
            container.innerHTML = result.data.html;
            container.scrollIntoView({ behavior: "smooth" });
          }
        }
      } catch (error) {
        console.error("Pagination error:", error);
      } finally {
        this.loading = false;
      }
    },

    scrollToTarget() {
      this.$nextTick(() => {
        const hash = window.location.hash;
        const container = document.querySelector('[x-ref="commentContainer"]');
        if (!container) return;

        let target = null;
        if (hash === "#comment") {
          target = document.getElementById("commentform");
        } else if (hash.startsWith("#comment-")) {
          target = document.querySelector(hash);
          window.location.hash = "#comment";
        }

        if (target) {
          const containerRect = container.getBoundingClientRect();
          const targetRect = target.getBoundingClientRect();
          const scrollPos =
            targetRect.top - containerRect.top + container.scrollTop;

          container.scrollTo({
            top: scrollPos - 20,
            behavior: "smooth",
          });

          if (hash === "#comment") {
            const textarea = document.getElementById("comment");
            if (textarea) textarea.focus();
          }
        }
      });
    },

    scrollToForm() {
      this.open = true;
      window.location.hash = "#comment";
      this.scrollToTarget();
    },

    async submitComment(e) {
      if (this.loading) return;
      this.loading = true;

      const form = e.target;
      const formData = new FormData(form);
      const parentId = formData.get("comment_parent");

      formData.append("action", "mc_ajax_post_comment");
      formData.append("security", mediumCloneData.comment_nonce);

      try {
        const response = await fetch(mediumCloneData.ajax_url, {
          method: "POST",
          body: formData,
        });

        const result = await response.json();

        if (result.success) {
          this.count = result.data.count;

          const temp = document.createElement("div");
          temp.innerHTML = result.data.html;
          const newComment = temp.firstElementChild;
          newComment.style.opacity = 0;

          if (parentId && parentId !== "0") {
            const parentComment = document.getElementById(
              `comment-${parentId}`,
            );
            if (parentComment) {
              let childrenContainer = parentComment.querySelector(".children");
              if (!childrenContainer) {
                childrenContainer = document.createElement("div");
                childrenContainer.className = "children";
                parentComment.appendChild(childrenContainer);
              }
              childrenContainer.appendChild(newComment);
            }
          } else {
            const list = document.querySelector(".comment-list");
            if (list) {
              list.prepend(newComment);
            }
          }

          gsap.to(newComment, { opacity: 1, duration: 0.5 });

          form.reset();
          const cancelBtn = document.getElementById(
            "cancel-comment-reply-link",
          );
          if (cancelBtn && cancelBtn.style.display !== "none")
            cancelBtn.click();
        } else {
          alert(result.data.message || "Erreur lors de l'envoi");
        }
      } catch (error) {
        console.error("Erreur complète:", error);
        alert("Une erreur est survenue lors de l'envoi du commentaire.");
      } finally {
        this.loading = false;
      }
    },
  }));

  Alpine.data("notificationsHandler", () => ({
    notifications: [],
    loading: false,

    init() {
      this.fetchNotifications();
    },

    async fetchNotifications() {
      this.loading = true;
      try {
        const response = await fetch(
          `${mediumCloneData.rest_url}mediumclone/v1/notifications`,
          {
            headers: {
              "X-WP-Nonce": mediumCloneData.nonce,
            },
          },
        );
        this.notifications = await response.json();
      } catch (error) {
        console.error("Error fetching notifications:", error);
      } finally {
        this.loading = false;
      }
    },

    async markAllAsRead() {
      try {
        await fetch(
          `${mediumCloneData.rest_url}mediumclone/v1/notifications/read`,
          {
            method: "POST",
            headers: {
              "X-WP-Nonce": mediumCloneData.nonce,
            },
          },
        );
        // Optimistic update
        this.notifications = this.notifications.map((n) => ({
          ...n,
          is_read: true,
        }));
      } catch (error) {
        console.error("Error marking notifications as read:", error);
      }
    },

    getUnreadCount() {
      return this.notifications.filter((n) => !n.is_read).length;
    },

    formatType(type) {
      const types = {
        follow: "started following you",
        like: "liked your story",
        comment: "responded to your story",
        reply: "replied to your comment",
      };
      return types[type] || "interacted with you";
    },

    formatTime(dateString) {
      if (!dateString) return "";
      const date = new Date(dateString);
      return date.toLocaleDateString(undefined, {
        month: "short",
        day: "numeric",
      });
    },
  }));

  Alpine.data("moderation", (postId) => ({
    loading: false,
    reported: false,
    async report() {
      if (!mediumCloneData.is_logged_in) {
        window.location.href = mediumCloneData.login_url;
        return;
      }

      if (!confirm("Voulez-vous vraiment signaler cette publication ?")) return;

      this.loading = true;
      const formData = new FormData();
      formData.append("action", "mc_report_post");
      formData.append("post_id", postId);
      formData.append("security", mediumCloneData.comment_nonce); // Using comment_nonce as a generic ajax nonce

      try {
        const res = await fetch(mediumCloneData.ajax_url, {
          method: "POST",
          body: formData,
        });
        const result = await res.json();
        if (result.success) {
          this.reported = true;
          alert(result.data.message);
          if (result.data.moderated) {
             window.location.href = mediumCloneData.root_url;
          }
        } else {
          alert(result.data.message || result.data);
        }
      } catch (err) {
        console.error(err);
        alert("Une erreur est survenue.");
      } finally {
        this.loading = false;
      }
    },
  }));
});

/* ═══════════════════════════════════════════════════════════════════════════
   PWA — Service Worker, Install Prompt, Push Notifications
   ═══════════════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  // Guard: mcPWA must be available (injected by PHP)
  if (typeof mcPWA === 'undefined') return;

  const pwa = mcPWA;

  /* ── 1. Service Worker Registration ─── */
  if ('serviceWorker' in navigator && pwa.sw_enabled) {
    window.addEventListener('load', async () => {
      try {
        const reg = await navigator.serviceWorker.register(pwa.sw_url, {
          scope: pwa.sw_scope,
        });

        console.log('[PWA] SW registered. Scope:', reg.scope);

        // Detect updates
        reg.addEventListener('updatefound', () => {
          const newWorker = reg.installing;
          if (!newWorker) return;

          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              mcPWAShowUpdateBanner(newWorker);
            }
          });
        });

        // Cache current page for offline access
        if (navigator.serviceWorker.controller) {
          navigator.serviceWorker.controller.postMessage({
            type: 'CACHE_PAGE',
            url: location.href,
          });
        }

      } catch (err) {
        console.warn('[PWA] SW registration failed:', err);
      }
    });
  }

  /* ── 2. Dynamic theme-color for dark mode ─── */
  function mcPWAUpdateThemeColor() {
    const isDark = document.documentElement.classList.contains('dark');
    const color = isDark ? (pwa.dark_theme_color || '#0f172a') : (pwa.theme_color || '#10b981');
    const meta = document.getElementById('mc-theme-color');
    if (meta) meta.content = color;
  }

  // Run on load + observe dark class changes
  mcPWAUpdateThemeColor();
  const htmlObserver = new MutationObserver(mcPWAUpdateThemeColor);
  htmlObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

  /* ── 3. Install Prompt (A2HS) ─── */
  let deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    // Only show if not dismissed recently
    const dismissed = localStorage.getItem('mc_pwa_install_dismissed');
    const dismissedAt = dismissed ? parseInt(dismissed) : 0;
    const daysSinceDismiss = (Date.now() - dismissedAt) / (1000 * 60 * 60 * 24);

    if (daysSinceDismiss > 7 || !dismissed) {
      setTimeout(() => mcPWAShowInstallBanner(), 3000);
    }
  });

  window.addEventListener('appinstalled', () => {
    console.log('[PWA] App installée avec succès !');
    deferredPrompt = null;
    const banner = document.getElementById('mc-pwa-install-banner');
    if (banner) banner.remove();
    localStorage.setItem('mc_pwa_installed', '1');
  });

  function mcPWAShowInstallBanner() {
    if (document.getElementById('mc-pwa-install-banner')) return;

    const banner = document.createElement('div');
    banner.id = 'mc-pwa-install-banner';
    banner.innerHTML = `
      <div style="
        position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(120px);
        background: var(--mc-pwa-bg, #1e293b);
        color: var(--mc-pwa-text, #f1f5f9);
        border: 1px solid rgba(16,185,129,.3);
        border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,.35);
        padding: 16px 20px; display: flex; align-items: center; gap: 14px;
        max-width: 420px; width: calc(100% - 40px); z-index: 99999;
        font-family: 'Inter', system-ui, sans-serif;
        animation: mcPWASlideUp .4s cubic-bezier(.34,1.56,.64,1) forwards;
        backdrop-filter: blur(12px);
      " id="mc-pwa-install-inner">
        <img src="${(typeof mediumCloneData !== 'undefined' ? mediumCloneData.root_url : '')}/wp-content/themes/theme-medium-clone/assets/images/icons/icon-72x72.png"
             width="44" height="44" style="border-radius:10px; flex-shrink:0; box-shadow:0 2px 8px rgba(0,0,0,.3);" alt="Icon">
        <div style="flex:1; min-width:0;">
          <div style="font-weight:700; font-size:.92rem; margin-bottom:2px;">${pwa.app_name || 'Medium Clone'}</div>
          <div style="font-size:.78rem; opacity:.75; line-height:1.4;">Installer l'application pour une expérience native hors-ligne</div>
        </div>
        <div style="display:flex; flex-direction:column; gap:6px; flex-shrink:0;">
          <button id="mc-pwa-install-btn" style="
            background: linear-gradient(135deg, #10b981, #059669);
            color:#fff; border:none; border-radius:8px; padding:7px 14px;
            font-size:.8rem; font-weight:700; cursor:pointer; white-space:nowrap;
            box-shadow:0 2px 8px rgba(16,185,129,.4);
          ">Installer</button>
          <button id="mc-pwa-dismiss-btn" style="
            background:transparent; color:rgba(148,163,184,.8);
            border:none; font-size:.75rem; cursor:pointer; padding:4px;
          ">Plus tard</button>
        </div>
      </div>
      <style>
        @keyframes mcPWASlideUp {
          from { transform: translateX(-50%) translateY(120px); opacity:0; }
          to   { transform: translateX(-50%) translateY(0);   opacity:1; }
        }
        :root { --mc-pwa-bg: #1e293b; --mc-pwa-text: #f1f5f9; }
        html:not(.dark) { --mc-pwa-bg: #ffffff; --mc-pwa-text: #0f172a; }
      </style>
    `;

    document.body.appendChild(banner);

    document.getElementById('mc-pwa-install-btn').addEventListener('click', async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      console.log('[PWA] Install outcome:', outcome);
      deferredPrompt = null;
      banner.remove();
    });

    document.getElementById('mc-pwa-dismiss-btn').addEventListener('click', () => {
      localStorage.setItem('mc_pwa_install_dismissed', Date.now().toString());
      const inner = document.getElementById('mc-pwa-install-inner');
      if (inner) {
        inner.style.transition = 'transform .3s ease, opacity .3s ease';
        inner.style.transform = 'translateX(-50%) translateY(120px)';
        inner.style.opacity = '0';
        setTimeout(() => banner.remove(), 320);
      }
    });
  }

  /* ── 4. Update Available Banner ─── */
  function mcPWAShowUpdateBanner(newWorker) {
    if (document.getElementById('mc-pwa-update-banner')) return;

    const banner = document.createElement('div');
    banner.id = 'mc-pwa-update-banner';
    banner.innerHTML = `
      <div style="
        position: fixed; top: 16px; right: 16px;
        background: var(--mc-pwa-bg, #1e293b);
        color: var(--mc-pwa-text, #f1f5f9);
        border: 1px solid rgba(99,102,241,.4);
        border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.25);
        padding: 14px 18px; display: flex; align-items: center; gap: 12px;
        z-index: 99999; font-family: 'Inter', system-ui, sans-serif;
        animation: mcPWAFadeIn .3s ease;
        max-width: 320px;
      ">
        <span style="font-size:1.4rem;">🔄</span>
        <div style="flex:1;">
          <div style="font-weight:700; font-size:.88rem;">Mise à jour disponible</div>
          <div style="font-size:.78rem; opacity:.7;">Rechargez pour obtenir la dernière version</div>
        </div>
        <button id="mc-pwa-update-btn" style="
          background:linear-gradient(135deg,#6366f1,#4f46e5);
          color:#fff; border:none; border-radius:7px; padding:6px 12px;
          font-size:.78rem; font-weight:700; cursor:pointer; flex-shrink:0;
        ">Recharger</button>
      </div>
      <style>
        @keyframes mcPWAFadeIn { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }
      </style>
    `;

    document.body.appendChild(banner);

    document.getElementById('mc-pwa-update-btn').addEventListener('click', () => {
      newWorker.postMessage({ type: 'SKIP_WAITING' });
      navigator.serviceWorker.addEventListener('controllerchange', () => {
        window.location.reload();
      });
    });
  }

  /* ── 5. Push Notifications ─── */
  if (pwa.push_enabled && pwa.vapid_public_key && 'PushManager' in window) {
    navigator.serviceWorker.ready.then(async (reg) => {
      try {
        const existing = await reg.pushManager.getSubscription();
        if (!existing) return; // Ne s'abonne pas automatiquement — attendre l'action utilisateur

        // Si déjà abonné, on re-synchronise avec le serveur
        await mcPWASendSubscriptionToServer(existing);
      } catch (err) {
        console.warn('[PWA] Push setup error:', err);
      }
    });
  }

  async function mcPWASubscribeToPush() {
    if (!pwa.vapid_public_key) {
      console.warn('[PWA] VAPID public key manquante.');
      return null;
    }

    try {
      const reg = await navigator.serviceWorker.ready;
      const subscription = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: mcPWAUrlBase64ToUint8Array(pwa.vapid_public_key),
      });

      await mcPWASendSubscriptionToServer(subscription);
      return subscription;
    } catch (err) {
      console.warn('[PWA] Push subscription error:', err);
      return null;
    }
  }

  async function mcPWASendSubscriptionToServer(subscription) {
    try {
      await fetch(pwa.push_subscribe_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': (typeof mediumCloneData !== 'undefined') ? mediumCloneData.nonce : '',
        },
        body: JSON.stringify(subscription.toJSON()),
      });
    } catch (err) {
      console.warn('[PWA] Failed to send subscription to server:', err);
    }
  }

  function mcPWAUrlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  // Expose globally pour usage depuis d'autres scripts
  window.mcPWA = {
    ...pwa,
    subscribeToNotifications: mcPWASubscribeToPush,
  };

})();
