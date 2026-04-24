const tabs = document.querySelectorAll(".tab");
const forms = document.querySelectorAll(".form");
const forgotToggle = document.getElementById("forgotToggle");
const backToLoginBtn = document.getElementById("backToLoginBtn");
const faceLoginBtn = document.getElementById("faceLoginBtn");
const faceStatus = document.getElementById("faceStatus");
const faceSignupOptIn = document.getElementById("faceSignupOptIn");
const faceSignupBtn = document.getElementById("faceSignupBtn");
const validateFaceSignupBtn = document.getElementById("validateFaceSignupBtn");
const faceSignupStatus = document.getElementById("faceSignupStatus");
const loginForm = document.getElementById("loginForm");
const signupForm = document.getElementById("signupForm");
const forgotForm = document.getElementById("forgotForm");
const forgotSubmitBtn = document.getElementById("forgotSubmitBtn");
const loginMessage = document.getElementById("loginMessage");
const signupMessage = document.getElementById("signupMessage");
const forgotMessage = document.getElementById("forgotMessage");
const inlineFaceVideo = document.getElementById("inlineFaceVideo");
const inlineFaceCanvas = document.getElementById("inlineFaceCanvas");
const faceModal = document.getElementById("faceModal");
const faceModalText = document.getElementById("faceModalText");
const faceVideo = document.getElementById("faceVideo");
const faceCanvas = document.getElementById("faceCanvas");
const captureFaceBtn = document.getElementById("captureFaceBtn");
const closeFaceModalBtn = document.getElementById("closeFaceModalBtn");
const faceChallengeProgress = document.getElementById("faceChallengeProgress");

const API = {
    signin: "/api/auth/signin",
    signup: "/api/auth/signup",
    forgot: "/api/auth/forgot",
    facePending: "/api/auth/face/pending",
    faceLogin: "/api/auth/face/login",
};

let pendingFaceReady = false;
let currentFaceMode = null;
let currentFaceStream = null;
let inlineFaceStream = null;
const FACE_MATRIX_SIZE = 32;
const FACE_CAPTURE_SAMPLES = 5;
const FACE_CAPTURE_DELAY_MS = 120;
const FACE_AUTO_SCAN_INTERVAL_MS = 900;
const FACE_AUTO_SCAN_RETRY_MS = 2200;
const FACE_AUTO_SCAN_STABLE_MATCHES = 1;
const FACE_AUTO_SCAN_MAX_DELTA = 0.085;
const FACE_CHALLENGE_INTERVAL_MS = 420;
const FACE_CHALLENGE_STEP_HOLD_FRAMES = 2;
const FACE_LOGIN_CANDIDATE_MAX_COUNT = 6;
const FACE_LOGIN_CANDIDATE_MIN_DELTA = 0.012;
let faceAutoScanSession = 0;
let faceAutoScanBusy = false;
let faceAutoScanStableFrames = 0;
let faceAutoScanLastMatrix = null;
let faceChallengeIndex = 0;
let faceChallengeHoldFrames = 0;
let browserFaceDetector = null;

const FACE_CHALLENGE_STEPS = [
    {
        id: "center",
        label: "Etape 1/5: regardez l ecran, visage bien centre.",
        shortLabel: "Centre",
        match: (geometry) => isFaceCentered(geometry),
    },
    {
        id: "left",
        label: "Etape 2/5: tournez ou decalez legerement la tete vers la gauche.",
        shortLabel: "Gauche",
        match: (geometry) => geometry.centerX <= 0.4 && geometry.area >= 0.08,
    },
    {
        id: "right",
        label: "Etape 3/5: tournez ou decalez legerement la tete vers la droite.",
        shortLabel: "Droite",
        match: (geometry) => geometry.centerX >= 0.6 && geometry.area >= 0.08,
    },
    {
        id: "up",
        label: "Etape 4/5: levez legerement le menton.",
        shortLabel: "Haut",
        match: (geometry) => geometry.centerY <= 0.36 && geometry.area >= 0.08,
    },
    {
        id: "center_confirm",
        label: "Etape 5/5: revenez bien au centre pour valider.",
        shortLabel: "Validation",
        match: (geometry) => isFaceCentered(geometry),
    },
];

const activate = (target) => {
    tabs.forEach((tab) => tab.classList.toggle("active", tab.dataset.target === target));
    forms.forEach((form) => form.classList.toggle("active", form.id === `${target}Form`));
};

const showForgot = () => {
    tabs.forEach((tab) => tab.classList.remove("active"));
    forms.forEach((form) => form.classList.toggle("active", form.id === "forgotForm"));
};

const setMessage = (el, text, type = "") => {
    if (!el) return;
    el.textContent = text;
    el.classList.remove("is-error", "is-success");
    if (type) {
        el.classList.add(type);
    }
};

const setHtmlMessage = (el, html, type = "") => {
    if (!el) return;
    el.innerHTML = html;
    el.classList.remove("is-error", "is-success");
    if (type) {
        el.classList.add(type);
    }
};

const setFaceStatus = (text, type = "") => setMessage(faceStatus, text, type);
const setFaceSignupStatus = (text, type = "") => setMessage(faceSignupStatus, text, type);
const setFaceModalStatus = (text, type = "") => setMessage(faceModalText, text, type);
const wait = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

const explainCameraError = (error) => {
    const raw = typeof error?.message === "string" ? error.message : "";
    const name = typeof error?.name === "string" ? error.name : "";

    if (!window.isSecureContext) {
        return "La camera exige un contexte securise. Utilisez http://localhost ou https.";
    }

    if (name === "NotAllowedError" || name === "PermissionDeniedError") {
        return "L acces a la camera a ete refuse.";
    }

    if (name === "NotFoundError" || name === "DevicesNotFoundError") {
        return "Aucune camera disponible sur cet appareil.";
    }

    return raw || "Impossible d ouvrir la camera.";
};

const clampCrop = (crop, width, height) => {
    const cropWidth = Math.max(1, Math.min(crop.width, width));
    const cropHeight = Math.max(1, Math.min(crop.height, height));

    return {
        x: Math.min(Math.max(0, crop.x), Math.max(0, width - cropWidth)),
        y: Math.min(Math.max(0, crop.y), Math.max(0, height - cropHeight)),
        width: cropWidth,
        height: cropHeight,
    };
};

const normalizeCapturedMatrix = (matrix) => {
    if (!Array.isArray(matrix) || matrix.length !== FACE_MATRIX_SIZE * FACE_MATRIX_SIZE) {
        throw new Error("Capture visage invalide.");
    }

    let min = Number.POSITIVE_INFINITY;
    let max = Number.NEGATIVE_INFINITY;

    for (const value of matrix) {
        min = Math.min(min, value);
        max = Math.max(max, value);
    }

    const range = max - min;
    if (range < 0.02) {
        throw new Error("Rapprochez votre visage et assurez un bon eclairage.");
    }

    return matrix.map((value) => Number(((value - min) / range).toFixed(6)));
};

function isFaceCentered(geometry) {
    return (
        Math.abs(geometry.centerX - 0.5) <= 0.13
        && Math.abs(geometry.centerY - 0.42) <= 0.14
        && geometry.area >= 0.08
        && geometry.area <= 0.42
    );
}

const averageMatrixDelta = (left, right) => {
    if (!Array.isArray(left) || !Array.isArray(right) || left.length !== right.length || left.length === 0) {
        return Number.POSITIVE_INFINITY;
    }

    let total = 0;
    for (let index = 0; index < left.length; index += 1) {
        total += Math.abs(Number(left[index] || 0) - Number(right[index] || 0));
    }

    return total / left.length;
};

const averageFaceMatrices = (samples) => samples[0].map((_, matrixIndex) => {
    let total = 0;
    for (const sample of samples) {
        total += sample[matrixIndex];
    }

    return Number((total / samples.length).toFixed(6));
});

const blendFaceMatrices = (left, right) => left.map((value, index) => {
    const blendedValue = (Number(value) + Number(right[index] || 0)) / 2;
    return Number(blendedValue.toFixed(6));
});

const sanitizeFaceCandidateMatrix = (matrix) => {
    if (!Array.isArray(matrix) || matrix.length !== FACE_MATRIX_SIZE * FACE_MATRIX_SIZE) {
        return null;
    }

    const sanitized = [];
    for (const value of matrix) {
        const numeric = Number(value);
        if (!Number.isFinite(numeric)) {
            return null;
        }

        const clamped = Math.min(1, Math.max(0, numeric));
        sanitized.push(Number(clamped.toFixed(6)));
    }

    return sanitized;
};

const pushUniqueFaceCandidate = (bucket, matrix) => {
    const sanitized = sanitizeFaceCandidateMatrix(matrix);
    if (!sanitized) {
        return;
    }

    const alreadyIncluded = bucket.some((existingMatrix) => averageMatrixDelta(existingMatrix, sanitized) <= FACE_LOGIN_CANDIDATE_MIN_DELTA);
    if (!alreadyIncluded) {
        bucket.push(sanitized);
    }
};

const resetFaceAutoScanState = () => {
    faceAutoScanBusy = false;
    faceAutoScanStableFrames = 0;
    faceAutoScanLastMatrix = null;
    faceChallengeIndex = 0;
    faceChallengeHoldFrames = 0;
    renderFaceChallengeProgress();
};

const hasDynamicFaceTracking = () => "FaceDetector" in window;

const getFaceDetector = () => {
    if (!hasDynamicFaceTracking()) {
        return null;
    }

    if (!browserFaceDetector) {
        browserFaceDetector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
    }

    return browserFaceDetector;
};

const renderFaceChallengeProgress = () => {
    if (!faceChallengeProgress) return;

    if (!hasDynamicFaceTracking()) {
        faceChallengeProgress.hidden = true;
        faceChallengeProgress.innerHTML = "";
        return;
    }

    faceChallengeProgress.hidden = false;
    faceChallengeProgress.innerHTML = FACE_CHALLENGE_STEPS.map((step, index) => {
        const state = index < faceChallengeIndex
            ? "is-done"
            : index === faceChallengeIndex
                ? "is-active"
                : "";

        return `<span class="face-challenge-pill ${state}">${step.shortLabel}</span>`;
    }).join("");
};

const captureSingleFaceMatrix = async (videoEl, canvasEl) => {
    const frame = await captureFaceFrame(videoEl, canvasEl);
    return frame.matrix;
};

const captureFaceFrame = async (videoEl, canvasEl) => {
    if (!(videoEl instanceof HTMLVideoElement) || !(canvasEl instanceof HTMLCanvasElement)) {
        throw new Error("Camera indisponible.");
    }

    const context = canvasEl.getContext("2d", { willReadFrequently: true });
    if (!context) {
        throw new Error("Canvas indisponible.");
    }

    const width = videoEl.videoWidth || 640;
    const height = videoEl.videoHeight || 480;
    canvasEl.width = width;
    canvasEl.height = height;
    context.drawImage(videoEl, 0, 0, width, height);

    const fallbackSize = Math.min(width * 0.62, height * 0.62);
    let crop = clampCrop(
        {
            x: (width - fallbackSize) / 2,
            y: height * 0.14,
            width: fallbackSize,
            height: fallbackSize,
        },
        width,
        height
    );

    let geometry = {
        centerX: (crop.x + crop.width / 2) / width,
        centerY: (crop.y + crop.height / 2) / height,
        area: (crop.width * crop.height) / (width * height),
        detectorUsed: false,
    };

    if (hasDynamicFaceTracking()) {
        try {
            const detector = getFaceDetector();
            if (!detector) {
                throw new Error("Suivi dynamique indisponible.");
            }

            const faces = await detector.detect(canvasEl);
            if (faces.length === 0) {
                throw new Error("Aucun visage detecte. Cadrez mieux votre visage.");
            }

            const box = faces[0].boundingBox;
            const size = Math.max(box.width, box.height) * 1.45;
            crop = clampCrop(
                {
                    x: box.x + (box.width - size) / 2,
                    y: box.y + (box.height - size) / 2 - size * 0.08,
                    width: size,
                    height: size,
                },
                width,
                height
            );
            geometry = {
                centerX: (box.x + box.width / 2) / width,
                centerY: (box.y + box.height / 2) / height,
                area: (box.width * box.height) / (width * height),
                detectorUsed: true,
            };
        } catch (error) {
            if (error instanceof Error && error.message.includes("Aucun visage detecte")) {
                throw error;
            }

            // fallback
        }
    }

    const matrixCanvas = document.createElement("canvas");
    matrixCanvas.width = FACE_MATRIX_SIZE;
    matrixCanvas.height = FACE_MATRIX_SIZE;
    const matrixContext = matrixCanvas.getContext("2d", { willReadFrequently: true });
    if (!matrixContext) {
        throw new Error("Canvas de matrice indisponible.");
    }

    matrixContext.drawImage(
        canvasEl,
        crop.x,
        crop.y,
        crop.width,
        crop.height,
        0,
        0,
        FACE_MATRIX_SIZE,
        FACE_MATRIX_SIZE
    );

    const { data } = matrixContext.getImageData(0, 0, FACE_MATRIX_SIZE, FACE_MATRIX_SIZE);
    const matrix = [];
    for (let index = 0; index < data.length; index += 4) {
        const red = data[index];
        const green = data[index + 1];
        const blue = data[index + 2];
        const grayscale = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
        matrix.push(grayscale);
    }

    return {
        matrix: normalizeCapturedMatrix(matrix),
        geometry,
    };
};

const postJson = async (url, payload) => {
    const response = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
    });
    const result = await parseApiResponse(response);

    if (!response.ok) {
        throw new Error(buildApiErrorMessage(result));
    }

    return result;
};

const parseApiResponse = async (response) => {
    const raw = await response.text();
    if (!raw) {
        return {};
    }

    try {
        return JSON.parse(raw);
    } catch (_error) {
        return {
            error: response.ok ? "Reponse serveur invalide." : "Le serveur a renvoye une erreur non JSON.",
            details: raw.slice(0, 160).replace(/\s+/g, " ").trim(),
        };
    }
};

const buildApiErrorMessage = (result) => {
    return [result?.error, result?.details].filter(Boolean).join(" ") || "Erreur serveur.";
};

const handleJsonSubmit = async (form, url, payload, messageEl, successText) => {
    const button = form.querySelector("button[type='submit']");
    if (!(button instanceof HTMLButtonElement)) return null;

    const original = button.textContent;
    button.textContent = "Traitement...";
    button.disabled = true;
    setMessage(messageEl, "");

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const result = await parseApiResponse(response);
        if (!response.ok) {
            const errorText = buildApiErrorMessage(result);
            setMessage(messageEl, errorText || "Erreur serveur.", "is-error");
            return null;
        }

        if (result.reset_url) {
            const baseMessage = successText || result.message || "Operation validee.";
            const verifyUrl = result.verify_code_url
                ? String(result.verify_code_url)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                : "";
            const resetCode = result.reset_code
                ? String(result.reset_code)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                : "";
            setHtmlMessage(
                messageEl,
                `${baseMessage}`
                + `${resetCode ? `<br>Code: <strong>${resetCode}</strong>` : ""}`
                + `${verifyUrl ? `<br><a href="${verifyUrl}" target="_blank" rel="noopener noreferrer">Ouvrir la page du code</a>` : ""}`,
                "is-success"
            );
            return result;
        }

        setMessage(messageEl, successText || result.message || "Operation validee.", "is-success");
        return result;
    } catch (e) {
        console.error("DEBUG FETCH ERR:", e);
        setMessage(messageEl, "Connexion serveur impossible. Erreur: " + e.message, "is-error");
        return null;
    } finally {
        button.textContent = original;
        button.disabled = false;
    }
};

const stopStream = (stream) => {
    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
    }
};

const stopInlineCamera = () => {
    stopStream(inlineFaceStream);
    inlineFaceStream = null;
    if (inlineFaceVideo) {
        inlineFaceVideo.srcObject = null;
    }
};

const stopModalCamera = () => {
    stopStream(currentFaceStream);
    currentFaceStream = null;
    if (faceVideo) {
        faceVideo.srcObject = null;
    }
};

const closeFaceModal = () => {
    faceAutoScanSession += 1;
    resetFaceAutoScanState();
    stopModalCamera();
    currentFaceMode = null;
    if (faceModal) {
        faceModal.hidden = true;
    }
};

const openLoginFaceModal = async () => {
    stopModalCamera();
    currentFaceMode = "login";
    faceAutoScanSession += 1;
    resetFaceAutoScanState();
    setFaceStatus("");
    setFaceModalStatus(
        hasDynamicFaceTracking()
            ? "Controle vivant active: centre, gauche, droite, haut, puis retour centre."
            : "Reconnaissance automatique active. Votre navigateur ne supporte pas le suivi dynamique du visage."
    );

    currentFaceStream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: "user",
            width: { ideal: 640 },
            height: { ideal: 480 },
        },
        audio: false,
    });

    if (faceVideo) {
        faceVideo.srcObject = currentFaceStream;
        await faceVideo.play();
    }

    if (faceModal) {
        faceModal.hidden = false;
    }

    renderFaceChallengeProgress();

    if (hasDynamicFaceTracking()) {
        startDynamicFaceLogin(faceAutoScanSession);
    } else {
        startAutoFaceLogin(faceAutoScanSession);
    }
};

const openInlineSignupCamera = async () => {
    stopInlineCamera();
    inlineFaceStream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: "user",
            width: { ideal: 640 },
            height: { ideal: 480 },
        },
        audio: false,
    });

    if (inlineFaceVideo) {
        inlineFaceVideo.srcObject = inlineFaceStream;
        await inlineFaceVideo.play();
    }
};

const buildFaceLoginBundleFromElements = async (videoEl, canvasEl, seedMatrices = []) => {
    const samples = [];
    let lastError = null;

    for (let index = 0; index < FACE_CAPTURE_SAMPLES; index += 1) {
        try {
            samples.push(await captureSingleFaceMatrix(videoEl, canvasEl));
        } catch (error) {
            lastError = error;
        }

        if (index < FACE_CAPTURE_SAMPLES - 1) {
            await wait(FACE_CAPTURE_DELAY_MS);
        }
    }

    if (samples.length < 3) {
        throw lastError || new Error("Capture visage impossible.");
    }

    const averagedMatrix = averageFaceMatrices(samples);
    const middleMatrix = samples[Math.floor(samples.length / 2)];
    const firstMatrix = samples[0];
    const lastMatrix = samples[samples.length - 1];
    const candidateMatrices = [];

    for (const seedMatrix of seedMatrices) {
        pushUniqueFaceCandidate(candidateMatrices, seedMatrix);
    }

    pushUniqueFaceCandidate(candidateMatrices, averagedMatrix);
    pushUniqueFaceCandidate(candidateMatrices, middleMatrix);
    pushUniqueFaceCandidate(candidateMatrices, firstMatrix);
    pushUniqueFaceCandidate(candidateMatrices, lastMatrix);
    pushUniqueFaceCandidate(candidateMatrices, blendFaceMatrices(firstMatrix, middleMatrix));
    pushUniqueFaceCandidate(candidateMatrices, blendFaceMatrices(middleMatrix, lastMatrix));

    return {
        primaryMatrix: candidateMatrices[0] || averagedMatrix,
        candidateMatrices: candidateMatrices.slice(0, FACE_LOGIN_CANDIDATE_MAX_COUNT),
    };
};

const buildFaceMatrixFromElements = async (videoEl, canvasEl) => {
    const bundle = await buildFaceLoginBundleFromElements(videoEl, canvasEl);
    return bundle.primaryMatrix;
};

const applySuccessfulFaceLogin = (result) => {
    setFaceStatus(result.message || "Connexion par visage validee.", "is-success");
    setFaceModalStatus(result.message || "Connexion par visage validee.", "is-success");
    closeFaceModal();

    if (result.redirect) {
        window.location.href = result.redirect;
    }
};

const submitFaceLogin = async (matrixOrMatrices, mode = "auto") => {
    const isManual = mode === "manual";
    const originalText = captureFaceBtn?.textContent || "";
    const rawCandidates = Array.isArray(matrixOrMatrices?.[0]) ? matrixOrMatrices : [matrixOrMatrices];
    const candidateMatrices = [];

    for (const candidate of rawCandidates) {
        pushUniqueFaceCandidate(candidateMatrices, candidate);
    }

    faceAutoScanBusy = true;
    if (captureFaceBtn) {
        captureFaceBtn.disabled = true;
        captureFaceBtn.textContent = isManual ? "Analyse..." : "Reconnaissance...";
    }

    try {
        if (candidateMatrices.length === 0) {
            throw new Error("Capture visage invalide.");
        }

        const result = await postJson(API.faceLogin, {
            matrix: candidateMatrices[0],
            matrices: candidateMatrices,
        });
        applySuccessfulFaceLogin(result);
        return true;
    } catch (error) {
        setFaceStatus(error.message || "Connexion par visage impossible.", "is-error");
        setFaceModalStatus(
            error.message || (isManual ? "Connexion par visage impossible." : "Visage non reconnu. Repositionnez-vous face camera."),
            "is-error"
        );
        return false;
    } finally {
        faceAutoScanBusy = false;
        if (captureFaceBtn) {
            captureFaceBtn.disabled = false;
            captureFaceBtn.textContent = originalText;
        }
    }
};

const startAutoFaceLogin = async (sessionId) => {
    while (
        sessionId === faceAutoScanSession
        && currentFaceMode === "login"
        && currentFaceStream
        && faceVideo instanceof HTMLVideoElement
    ) {
        let delayMs = FACE_AUTO_SCAN_INTERVAL_MS;

        if (faceAutoScanBusy) {
            await wait(delayMs);
            continue;
        }

        try {
            const loginBundle = await buildFaceLoginBundleFromElements(faceVideo, faceCanvas);
            const matrix = loginBundle.primaryMatrix;
            const delta = faceAutoScanLastMatrix === null
                ? 0
                : averageMatrixDelta(faceAutoScanLastMatrix, matrix);

            if (faceAutoScanLastMatrix === null || delta <= FACE_AUTO_SCAN_MAX_DELTA) {
                faceAutoScanStableFrames += 1;
            } else {
                faceAutoScanStableFrames = 1;
            }

            faceAutoScanLastMatrix = matrix;

            if (faceAutoScanStableFrames < FACE_AUTO_SCAN_STABLE_MATCHES) {
                setFaceModalStatus("Visage detecte. Ne bougez plus une seconde...");
                await wait(delayMs);
                continue;
            }

            setFaceModalStatus("Visage stable detecte. Verification automatique...");
            const success = await submitFaceLogin(loginBundle.candidateMatrices, "auto");
            if (success) {
                return;
            }

            resetFaceAutoScanState();
            delayMs = FACE_AUTO_SCAN_RETRY_MS;
        } catch (error) {
            resetFaceAutoScanState();
            setFaceModalStatus(error.message || "Cadrez votre visage face camera.");
        }

        await wait(delayMs);
    }
};

const startDynamicFaceLogin = async (sessionId) => {
    renderFaceChallengeProgress();

    while (
        sessionId === faceAutoScanSession
        && currentFaceMode === "login"
        && currentFaceStream
        && faceVideo instanceof HTMLVideoElement
    ) {
        if (faceAutoScanBusy) {
            await wait(FACE_CHALLENGE_INTERVAL_MS);
            continue;
        }

        try {
            const frame = await captureFaceFrame(faceVideo, faceCanvas);
            if (!frame.geometry.detectorUsed) {
                setFaceModalStatus("Suivi dynamique indisponible. Passage au scan automatique classique.");
                startAutoFaceLogin(sessionId);
                return;
            }

            const currentStep = FACE_CHALLENGE_STEPS[faceChallengeIndex];
            if (!currentStep) {
                setFaceModalStatus("Verification finale...");
                const loginBundle = await buildFaceLoginBundleFromElements(faceVideo, faceCanvas, [frame.matrix]);
                const success = await submitFaceLogin(loginBundle.candidateMatrices, "auto");
                if (success) {
                    return;
                }

                resetFaceAutoScanState();
                await wait(FACE_AUTO_SCAN_RETRY_MS);
                continue;
            }

            setFaceModalStatus(currentStep.label);

            if (currentStep.match(frame.geometry)) {
                faceChallengeHoldFrames += 1;
            } else {
                faceChallengeHoldFrames = 0;
            }

            if (faceChallengeHoldFrames >= FACE_CHALLENGE_STEP_HOLD_FRAMES) {
                faceChallengeIndex += 1;
                faceChallengeHoldFrames = 0;
                renderFaceChallengeProgress();

                if (faceChallengeIndex >= FACE_CHALLENGE_STEPS.length) {
                    setFaceModalStatus("Mouvement valide. Verification du visage...");
                    const loginBundle = await buildFaceLoginBundleFromElements(faceVideo, faceCanvas, [frame.matrix]);
                    const success = await submitFaceLogin(loginBundle.candidateMatrices, "auto");
                    if (success) {
                        return;
                    }

                    resetFaceAutoScanState();
                    await wait(FACE_AUTO_SCAN_RETRY_MS);
                    continue;
                }
            }
        } catch (error) {
            faceChallengeHoldFrames = 0;
            setFaceModalStatus(error.message || "Cadrez votre visage pour continuer le scan.");
        }

        await wait(FACE_CHALLENGE_INTERVAL_MS);
    }
};

const getSignupPayload = () => ({
    last_name: signupForm.last_name.value.trim(),
    first_name: signupForm.first_name.value.trim(),
    shop_name: signupForm.shop_name.value.trim(),
    phone: signupForm.phone.value.trim(),
    location: signupForm.location.value.trim(),
    email: signupForm.email.value.trim(),
    password: signupForm.password.value,
});

const refreshFaceSignupStatus = () => {
    if (!faceSignupOptIn?.checked) {
        setFaceSignupStatus("Le compte sera cree sans matrice faciale.");
        return;
    }

    if (pendingFaceReady) {
        setFaceSignupStatus("Visage valide et enregistre. Vous pouvez creer le compte.", "is-success");
        return;
    }

    setFaceSignupStatus("Ouvrez la camera puis cliquez sur valider le visage.");
};

tabs.forEach((tab) => {
    tab.addEventListener("click", () => activate(tab.dataset.target));
});

if (backToLoginBtn) {
    backToLoginBtn.addEventListener("click", () => activate("login"));
}

if (closeFaceModalBtn) {
    closeFaceModalBtn.addEventListener("click", closeFaceModal);
}

if (faceSignupOptIn) {
    faceSignupOptIn.addEventListener("change", () => {
        if (!faceSignupOptIn.checked) {
            pendingFaceReady = false;
            stopInlineCamera();
        }
        refreshFaceSignupStatus();
    });
}

if (faceSignupBtn) {
    faceSignupBtn.addEventListener("click", async () => {
        try {
            await openInlineSignupCamera();
            setFaceSignupStatus("Camera ouverte. Cliquez sur valider le visage.", "is-success");
        } catch (error) {
            setFaceSignupStatus(explainCameraError(error), "is-error");
        }
    });
}

if (validateFaceSignupBtn) {
    validateFaceSignupBtn.addEventListener("click", async () => {
        validateFaceSignupBtn.disabled = true;

        try {
            const matrix = await buildFaceMatrixFromElements(inlineFaceVideo, inlineFaceCanvas);
            const result = await postJson(API.facePending, { matrix });
            pendingFaceReady = true;
            setFaceSignupStatus(result.message || "Visage valide et enregistre.", "is-success");
            stopInlineCamera();
        } catch (error) {
            pendingFaceReady = false;
            setFaceSignupStatus(error.message || "Validation du visage impossible.", "is-error");
        } finally {
            validateFaceSignupBtn.disabled = false;
        }
    });
}

if (faceLoginBtn) {
    faceLoginBtn.addEventListener("click", async () => {
        try {
            await openLoginFaceModal();
        } catch (error) {
            setFaceStatus(explainCameraError(error), "is-error");
        }
    });
}

if (captureFaceBtn) {
    captureFaceBtn.addEventListener("click", async () => {
        try {
            setFaceModalStatus("Analyse manuelle du visage en cours...");
            const loginBundle = await buildFaceLoginBundleFromElements(faceVideo, faceCanvas);
            resetFaceAutoScanState();
            await submitFaceLogin(loginBundle.candidateMatrices, "manual");
        } catch (error) {
            setFaceStatus(error.message || "Connexion par visage impossible.", "is-error");
            setFaceModalStatus(error.message || "Connexion par visage impossible.", "is-error");
        }
    });
}

if (loginForm) {
    loginForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const result = await handleJsonSubmit(
            loginForm,
            API.signin,
            {
                email: loginForm.email.value.trim(),
                password: loginForm.password.value,
            },
            loginMessage,
            "Connexion validee."
        );

        if (result?.redirect) {
            window.location.href = result.redirect;
        }
    });
}

if (signupForm) {
    signupForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        
        if (signupForm.dataset.submitting === "1") return;

        if (signupForm.password.value !== signupForm.password_confirm.value) {
            setMessage(signupMessage, "Les mots de passe ne correspondent pas.", "is-error");
            return;
        }

        const wantsFace = !!(faceSignupOptIn && faceSignupOptIn.checked);
        if (wantsFace && !pendingFaceReady) {
            setFaceSignupStatus("Compte cree sans visage pour le moment. Vous pourrez ajouter votre visage plus tard dans Mon compte.");
        }

        signupForm.dataset.submitting = "1";
        try {
            const result = await handleJsonSubmit(
                signupForm,
                API.signup,
                getSignupPayload(),
                signupMessage,
                "Compte cree avec succes."
            );

            if (result?.redirect) {
                window.location.href = result.redirect;
                return;
            }
            
            // If no redirect, switch to login tab
            if (result) {
                activate("login");
                if (loginMessage) {
                    setMessage(loginMessage, "Compte cree avec succes. Vous pouvez vous connecter.", "is-success");
                }
                signupForm.reset();
            }
        } finally {
            signupForm.dataset.submitting = "0";
        }
    });
}

if (forgotForm) {
    const submitForgotForm = async (event) => {
        event.preventDefault();

        const emailInput = forgotForm.querySelector("input[name='recovery_email']");
        const emailValue = emailInput instanceof HTMLInputElement ? emailInput.value.trim() : "";
        if (!emailValue) {
            setMessage(forgotMessage, "Email obligatoire.", "is-error");
            return;
        }

        await handleJsonSubmit(
            forgotForm,
            API.forgot,
            {
                email: emailValue,
            },
            forgotMessage
        );
    };

    forgotForm.addEventListener("submit", submitForgotForm);

    if (forgotSubmitBtn) {
        forgotSubmitBtn.addEventListener("click", submitForgotForm);
    }
}

refreshFaceSignupStatus();
setFaceStatus("Cliquez sur le bouton pour ouvrir la camera et vous connecter par visage.");
