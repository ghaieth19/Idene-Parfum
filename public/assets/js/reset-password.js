const resetForm = document.getElementById("resetPasswordForm");
const resetMessage = document.getElementById("resetPasswordMessage");
const resetTokenInput = document.getElementById("resetToken");

const setResetMessage = (text, type = "") => {
    if (!resetMessage) return;
    resetMessage.textContent = text;
    resetMessage.classList.remove("is-error", "is-success");
    if (type) {
        resetMessage.classList.add(type);
    }
};

const token = new URLSearchParams(window.location.search).get("token") || "";
if (resetTokenInput) {
    resetTokenInput.value = token;
}

if (!token) {
    setResetMessage("Le lien de reinitialisation est incomplet.", "is-error");
}

if (resetForm) {
    resetForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!token) {
            setResetMessage("Le lien de reinitialisation est invalide.", "is-error");
            return;
        }

        const button = resetForm.querySelector("button[type='submit']");
        if (!(button instanceof HTMLButtonElement)) return;

        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = "Traitement...";
        setResetMessage("");

        try {
            const response = await fetch("/api/auth/reset", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    token,
                    password: resetForm.password.value,
                    password_confirm: resetForm.password_confirm.value,
                }),
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                setResetMessage(result.error || "Erreur serveur.", "is-error");
                return;
            }

            setResetMessage(result.message || "Mot de passe mis a jour.", "is-success");
            resetForm.reset();
            window.setTimeout(() => {
                window.location.href = "/auth";
            }, 1500);
        } catch {
            setResetMessage("Connexion serveur impossible.", "is-error");
        } finally {
            button.disabled = false;
            button.textContent = originalLabel;
        }
    });
}
