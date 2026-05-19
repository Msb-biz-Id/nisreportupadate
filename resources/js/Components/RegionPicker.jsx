import { useEffect, useState } from 'react';
import axios from 'axios';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export default function RegionPicker({ value, onChange }) {
    const [provinces, setProvinces] = useState([]);
    const [cities, setCities] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [villages, setVillages] = useState([]);

    useEffect(() => {
        axios.get(route('regions.provinces')).then((r) => setProvinces(r.data));
    }, []);

    useEffect(() => {
        if (value.provinsi_code) {
            axios.get(route('regions.cities'), { params: { province: value.provinsi_code } })
                .then((r) => setCities(r.data));
        } else {
            setCities([]);
        }
    }, [value.provinsi_code]);

    useEffect(() => {
        if (value.kabupaten_code) {
            axios.get(route('regions.districts'), { params: { city: value.kabupaten_code } })
                .then((r) => setDistricts(r.data));
        } else {
            setDistricts([]);
        }
    }, [value.kabupaten_code]);

    useEffect(() => {
        if (value.kecamatan_code) {
            axios.get(route('regions.villages'), { params: { district: value.kecamatan_code } })
                .then((r) => setVillages(r.data));
        } else {
            setVillages([]);
        }
    }, [value.kecamatan_code]);

    function pickProvince(code) {
        const p = provinces.find((x) => x.code === code);
        onChange({
            ...value,
            provinsi_code: code,
            provinsi_nama: p?.name ?? '',
            kabupaten_code: '', kabupaten_nama: '',
            kecamatan_code: '', kecamatan_nama: '',
            desa_code: '', desa_nama: '',
        });
    }
    function pickCity(code) {
        const c = cities.find((x) => x.code === code);
        onChange({
            ...value,
            kabupaten_code: code,
            kabupaten_nama: c?.name ?? '',
            kecamatan_code: '', kecamatan_nama: '',
            desa_code: '', desa_nama: '',
        });
    }
    function pickDistrict(code) {
        const d = districts.find((x) => x.code === code);
        onChange({
            ...value,
            kecamatan_code: code,
            kecamatan_nama: d?.name ?? '',
            desa_code: '', desa_nama: '',
        });
    }
    function pickVillage(code) {
        const v = villages.find((x) => x.code === code);
        onChange({
            ...value,
            desa_code: code,
            desa_nama: v?.name ?? '',
        });
    }

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <Label>Provinsi</Label>
                <Select value={value.provinsi_code || ''} onValueChange={pickProvince}>
                    <SelectTrigger className="mt-1.5"><SelectValue placeholder="Pilih provinsi" /></SelectTrigger>
                    <SelectContent className="max-h-72">
                        {provinces.map((p) => (
                            <SelectItem key={p.code} value={p.code}>{p.name}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div>
                <Label>Kabupaten / Kota</Label>
                <Select value={value.kabupaten_code || ''} onValueChange={pickCity} disabled={!value.provinsi_code}>
                    <SelectTrigger className="mt-1.5"><SelectValue placeholder="Pilih kabupaten" /></SelectTrigger>
                    <SelectContent className="max-h-72">
                        {cities.map((c) => (
                            <SelectItem key={c.code} value={c.code}>{c.name}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div>
                <Label>Kecamatan</Label>
                <Select value={value.kecamatan_code || ''} onValueChange={pickDistrict} disabled={!value.kabupaten_code}>
                    <SelectTrigger className="mt-1.5"><SelectValue placeholder="Pilih kecamatan" /></SelectTrigger>
                    <SelectContent className="max-h-72">
                        {districts.map((d) => (
                            <SelectItem key={d.code} value={d.code}>{d.name}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div>
                <Label>Desa / Kelurahan</Label>
                <Select value={value.desa_code || ''} onValueChange={pickVillage} disabled={!value.kecamatan_code}>
                    <SelectTrigger className="mt-1.5"><SelectValue placeholder="Pilih desa" /></SelectTrigger>
                    <SelectContent className="max-h-72">
                        {villages.map((v) => (
                            <SelectItem key={v.code} value={v.code}>{v.name}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </div>
    );
}
