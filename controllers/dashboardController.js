const db = require("../config/db");

exports.getDistribusiBulanan = async (req, res) => {
  try {
    const months = Math.max(1, parseInt(req.query.months || "6", 10));
    const idGudang = req.query.id_gudang ? Number(req.query.id_gudang) : null;
    const role = String(req.query.role || "").toLowerCase();

    const now = new Date();
    const startDate = new Date(now.getFullYear(), now.getMonth() - (months - 1), 1);
    const endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    const formatDate = (d) => d.toISOString().slice(0, 10);

    let sql = `
      SELECT
        YEAR(m.tanggal) AS tahun,
        MONTH(m.tanggal) AS bulan,
        COALESCE(SUM(md.jumlah), 0) AS total
      FROM mutasi m
      JOIN mutasi_detail md ON m.id = md.id_mutasi
      WHERE m.tanggal BETWEEN ? AND ?
    `;

    const params = [formatDate(startDate), formatDate(endDate)];

    if (role !== "dinkes" && idGudang) {
      sql += ` AND m.sumber = ? `;
      params.push(idGudang);
    }

    sql += `
      GROUP BY YEAR(m.tanggal), MONTH(m.tanggal)
      ORDER BY YEAR(m.tanggal), MONTH(m.tanggal)
    `;

    const [rows] = await db.query(sql, params);

    const monthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

    const baseMonths = [];
    for (let i = months - 1; i >= 0; i--) {
      const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
      baseMonths.push({
        tahun: d.getFullYear(),
        bulanAngka: d.getMonth() + 1,
        bulan: monthNames[d.getMonth()],
        total: 0,
      });
    }

    const map = new Map(
      baseMonths.map((m) => [`${m.tahun}-${m.bulanAngka}`, { ...m }])
    );

    rows.forEach((row) => {
      const key = `${row.tahun}-${row.bulan}`;
      if (map.has(key)) {
        map.get(key).total = Number(row.total || 0);
      }
    });

    const data = baseMonths.map((m) => map.get(`${m.tahun}-${m.bulanAngka}`));

    res.json({
      success: true,
      data,
    });
  } catch (error) {
    console.error("getDistribusiBulanan error:", error);
    res.status(500).json({
      success: false,
      message: "Gagal mengambil distribusi bulanan",
      error: error.message,
    });
  }
};