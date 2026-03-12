const express = require("express");
const router = express.Router();
const { getDistribusiBulanan } = require("../controllers/dashboardController");

router.get("/distribusi-bulanan", getDistribusiBulanan);

module.exports = router;