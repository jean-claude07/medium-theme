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
          window.location.href = mediumCloneData.root_url;
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

    toggleEdit() {
      this.isEditing = !this.isEditing;
      this.message = "";
      if (!this.isEditing) {
        this.form = {
          id: null,
          title: "",
          content: "",
          youtube_url: "",
          social_link: "",
          featured_image: null,
          categories: [],
        };
        this.quill.setContents([]);
      }
    },

    handleFileUpload(event) {
      const file = event.target.files[0];
      if (file) {
        this.form.featured_image = file;
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
          img.closest('[x-show]')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

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
});
